<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Http;

use Illuminate\Foundation\Testing\WithFaker;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Infrastructure\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Models\InvoiceProductLineModel;
use Tests\TestCase;

final class InvoiceControllerTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
    }

    public function testCreateInvoiceWithoutProductLines(): void
    {
        $requestData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/invoices', $requestData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'status',
                    'product_lines',
                    'total_price',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'status' => StatusEnum::Draft->value,
                    'total_price' => 0,
                    'product_lines' => [],
                ],
                'message' => 'Invoice created successfully',
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'status' => StatusEnum::Draft->value,
        ]);
    }

    public function testCreateInvoiceWithProductLines(): void
    {
        $requestData = [
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'product_lines' => [
                [
                    'name' => 'Product 1',
                    'quantity' => 2,
                    'unit_price' => 1000,
                ],
                [
                    'name' => 'Product 2',
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->postJson('/api/invoices', $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'customer_name' => 'Jane Smith',
                    'customer_email' => 'jane@example.com',
                    'status' => StatusEnum::Draft->value,
                    'total_price' => 2500, // (2*1000) + (1*500)
                    'product_lines' => [
                        [
                            'name' => 'Product 1',
                            'quantity' => 2,
                            'unit_price' => 1000,
                            'total_unit_price' => 2000,
                        ],
                        [
                            'name' => 'Product 2',
                            'quantity' => 1,
                            'unit_price' => 500,
                            'total_unit_price' => 500,
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'status' => StatusEnum::Draft->value,
        ]);

        $this->assertDatabaseCount('invoice_product_lines', 2);
    }

    public function testCreateInvoiceValidationErrors(): void
    {
        $requestData = [
            'customer_name' => '',
            'customer_email' => 'invalid-email',
        ];

        $response = $this->postJson('/api/invoices', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_email']);
    }

    public function testViewExistingInvoice(): void
    {
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::Draft->value,
        ]);

        $productLine = InvoiceProductLineModel::create([
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 3,
            'price' => 750,
        ]);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'status',
                    'product_lines' => [
                        '*' => [
                            'id',
                            'name',
                            'quantity',
                            'unit_price',
                            'total_unit_price',
                        ],
                    ],
                    'total_price',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $invoice->id,
                    'customer_name' => 'Test Customer',
                    'customer_email' => 'test@example.com',
                    'status' => StatusEnum::Draft->value,
                    'total_price' => 2250, // 3 * 750
                    'product_lines' => [
                        [
                            'name' => 'Test Product',
                            'quantity' => 3,
                            'unit_price' => 750,
                            'total_unit_price' => 2250,
                        ],
                    ],
                ],
            ]);
    }

    public function testViewNonExistentInvoice(): void
    {
        $nonExistentId = $this->faker->uuid();

        $response = $this->getJson("/api/invoices/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Invoice not found',
            ]);
    }

    public function testSendInvoiceSuccess(): void
    {
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

        $response = $this->postJson("/api/invoices/{$invoice->id}/send");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $invoice->id,
                    'status' => StatusEnum::Sending->value,
                ],
                'message' => 'Invoice sent successfully',
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => StatusEnum::Sending->value,
        ]);
    }

    public function testSendInvoiceWithoutProductLines(): void
    {
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::Draft->value,
        ]);

        $response = $this->postJson("/api/invoices/{$invoice->id}/send");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Invoice cannot be sent',
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => StatusEnum::Draft->value, // Should remain draft
        ]);
    }

    public function testSendInvoiceWithInvalidProductLines(): void
    {
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::Draft->value,
        ]);

        InvoiceProductLineModel::create([
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 0, // Invalid: must be > 0
            'price' => 1000,
        ]);

        $response = $this->postJson("/api/invoices/{$invoice->id}/send");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Invoice cannot be sent',
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => StatusEnum::Draft->value, // Should remain draft
        ]);
    }

    public function testSendNonDraftInvoice(): void
    {
        $invoice = InvoiceModel::create([
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => StatusEnum::Sending->value, // Already sending
        ]);

        InvoiceProductLineModel::create([
            'invoice_id' => $invoice->id,
            'name' => 'Test Product',
            'quantity' => 1,
            'price' => 1000,
        ]);

        $response = $this->postJson("/api/invoices/{$invoice->id}/send");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Invoice cannot be sent',
            ]);
    }

    public function testSendNonExistentInvoice(): void
    {
        $nonExistentId = $this->faker->uuid();

        $response = $this->postJson("/api/invoices/{$nonExistentId}/send");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Invoice not found',
            ]);
    }
}