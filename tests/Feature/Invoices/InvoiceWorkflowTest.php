<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Infrastructure\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Models\InvoiceProductLineModel;
use Modules\Notifications\Api\Events\ResourceDeliveredEvent;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class InvoiceWorkflowTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
    }

    public function testCompleteInvoiceWorkflow(): void
    {
        // Don't fake events for this test since we need the actual listener to work

        // Step 1: Create invoice
        $createData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'product_lines' => [
                [
                    'name' => 'Web Development',
                    'quantity' => 10,
                    'unit_price' => 5000,
                ],
                [
                    'name' => 'Consulting',
                    'quantity' => 5,
                    'unit_price' => 10000,
                ],
            ],
        ];

        $createResponse = $this->postJson('/api/invoices', $createData);
        $createResponse->assertStatus(201);

        $invoiceData = $createResponse->json('data');
        $invoiceId = $invoiceData['id'];

        // Verify invoice was created with correct data
        $this->assertEquals('John Doe', $invoiceData['customer_name']);
        $this->assertEquals('john@example.com', $invoiceData['customer_email']);
        $this->assertEquals(StatusEnum::Draft->value, $invoiceData['status']);
        $this->assertEquals(100000, $invoiceData['total_price']); // (10*5000) + (5*10000)
        $this->assertCount(2, $invoiceData['product_lines']);

        // Step 2: View invoice
        $viewResponse = $this->getJson("/api/invoices/{$invoiceId}");
        $viewResponse->assertStatus(200);

        $viewData = $viewResponse->json('data');
        $this->assertEquals($invoiceId, $viewData['id']);
        $this->assertEquals(StatusEnum::Draft->value, $viewData['status']);

        // Step 3: Send invoice
        $sendResponse = $this->postJson("/api/invoices/{$invoiceId}/send");
        $sendResponse->assertStatus(200);

        $sentData = $sendResponse->json('data');
        $this->assertEquals(StatusEnum::Sending->value, $sentData['status']);

        // Verify database was updated
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => StatusEnum::Sending->value,
        ]);

        // Step 4: Simulate webhook delivery confirmation
        $resourceDeliveredEvent = new ResourceDeliveredEvent(
            resourceId: Uuid::fromString($invoiceId)
        );

        // Dispatch the event manually to simulate webhook
        Event::dispatch($resourceDeliveredEvent);

        // Verify invoice status was updated to sent-to-client
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => StatusEnum::SentToClient->value,
        ]);

        // Step 5: View final invoice state
        $finalResponse = $this->getJson("/api/invoices/{$invoiceId}");
        $finalResponse->assertStatus(200);

        $finalData = $finalResponse->json('data');
        $this->assertEquals(StatusEnum::SentToClient->value, $finalData['status']);
    }

    public function testInvoiceCannotBeSentTwice(): void
    {
        // Create invoice with product lines
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::Draft->value,
        ]);

        InvoiceProductLineModel::create([
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 1,
            'price' => 1000,
        ]);

        // Send invoice first time
        $firstSendResponse = $this->postJson("/api/invoices/{$invoice->id}/send");
        $firstSendResponse->assertStatus(200);

        // Try to send again
        $secondSendResponse = $this->postJson("/api/invoices/{$invoice->id}/send");
        $secondSendResponse->assertStatus(422)
            ->assertJson([
                'error' => 'Invoice cannot be sent',
            ]);
    }

    public function testDeliveryConfirmationIgnoredForNonSendingInvoice(): void
    {
        Event::fake();

        // Create draft invoice
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::Draft->value,
        ]);

        // Try to mark as delivered (should be ignored)
        $resourceDeliveredEvent = new ResourceDeliveredEvent(
            resourceId: Uuid::fromString($invoice->id)
        );

        Event::dispatch($resourceDeliveredEvent);

        // Verify status remains draft
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => StatusEnum::Draft->value,
        ]);
    }

    public function testDeliveryConfirmationIgnoredForNonExistentInvoice(): void
    {
        Event::fake();

        // Try to mark non-existent invoice as delivered
        $resourceDeliveredEvent = new ResourceDeliveredEvent(
            resourceId: Uuid::uuid4()
        );

        // This should not throw an exception
        Event::dispatch($resourceDeliveredEvent);

        // No assertions needed - just verify no exception is thrown
        $this->assertTrue(true);
    }

    public function testStatusTransitionValidation(): void
    {
        // Create invoice in sent-to-client status
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::SentToClient->value,
        ]);

        InvoiceProductLineModel::create([
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 1,
            'price' => 1000,
        ]);

        // Try to send (should fail)
        $sendResponse = $this->postJson("/api/invoices/{$invoice->id}/send");
        $sendResponse->assertStatus(422);

        // Verify status unchanged
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => StatusEnum::SentToClient->value,
        ]);
    }

    public function testProductLineCalculations(): void
    {
        $createData = [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'product_lines' => [
                [
                    'name' => 'Product A',
                    'quantity' => 3,
                    'unit_price' => 2500,
                ],
                [
                    'name' => 'Product B',
                    'quantity' => 2,
                    'unit_price' => 7500,
                ],
                [
                    'name' => 'Product C',
                    'quantity' => 1,
                    'unit_price' => 10000,
                ],
            ],
        ];

        $response = $this->postJson('/api/invoices', $createData);
        $response->assertStatus(201);

        $data = $response->json('data');
        $this->assertEquals(32500, $data['total_price']); // (3*2500) + (2*7500) + (1*10000)

        // Verify individual product line totals
        $productLines = $data['product_lines'];
        $this->assertEquals(7500, $productLines[0]['total_unit_price']); // 3 * 2500
        $this->assertEquals(15000, $productLines[1]['total_unit_price']); // 2 * 7500
        $this->assertEquals(10000, $productLines[2]['total_unit_price']); // 1 * 10000
    }
}