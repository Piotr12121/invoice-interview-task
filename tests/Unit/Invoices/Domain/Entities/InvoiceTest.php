<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Entities;

use DateTimeImmutable;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceTest extends TestCase
{
    public function testCreateInvoice(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('John Doe', $invoice->getCustomerName()->toString());
        $this->assertEquals('john@example.com', $invoice->getCustomerEmail()->toString());
        $this->assertEquals(StatusEnum::Draft, $invoice->getStatus());
        $this->assertTrue($invoice->isDraft());
        $this->assertFalse($invoice->isSending());
        $this->assertFalse($invoice->isSentToClient());
        $this->assertEmpty($invoice->getProductLines());
    }

    public function testAddProductLine(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Test Product',
            quantity: 2,
            unitPrice: 1000
        );

        $invoice->addProductLine($productLine);

        $this->assertCount(1, $invoice->getProductLines());
        $this->assertEquals('Test Product', $invoice->getProductLines()[0]->getName());
    }

    public function testGetTotalPriceWithMultipleProductLines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $productLine1 = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Product 1',
            quantity: 2,
            unitPrice: 1000
        );
        
        $productLine2 = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Product 2',
            quantity: 1,
            unitPrice: 500
        );

        $invoice->addProductLine($productLine1);
        $invoice->addProductLine($productLine2);

        $this->assertEquals(2500, $invoice->getTotalPrice()->toInt()); // (2*1000) + (1*500)
    }

    public function testCanBeSentWithValidProductLines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Test Product',
            quantity: 1,
            unitPrice: 1000
        );
        
        $invoice->addProductLine($productLine);

        $this->assertTrue($invoice->canBeSent());
    }

    public function testCannotBeSentWithEmptyProductLines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        $this->assertFalse($invoice->canBeSent());
    }

    public function testCannotBeSentWithInvalidProductLines(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Test Product',
            quantity: 0, // Invalid: must be > 0
            unitPrice: 1000
        );
        
        $invoice->addProductLine($productLine);

        $this->assertFalse($invoice->canBeSent());
    }

    public function testMarkAsSendingSuccess(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Test Product',
            quantity: 1,
            unitPrice: 1000
        );
        
        $invoice->addProductLine($productLine);

        $invoice->markAsSending();

        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
        $this->assertTrue($invoice->isSending());
    }

    public function testMarkAsSendingFailsWhenNotDraft(): void
    {
        $this->expectException(InvalidInvoiceStatusException::class);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Test Product',
            quantity: 1,
            unitPrice: 1000
        );
        
        $invoice->addProductLine($productLine);
        $invoice->markAsSending(); // First transition to Sending
        $invoice->markAsSending(); // This should fail
    }

    public function testMarkAsSendingFailsWithoutValidProductLines(): void
    {
        $this->expectException(InvoiceCannotBeSentException::class);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->markAsSending(); // Should fail - no product lines
    }

    public function testMarkAsSentToClientSuccess(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');
        
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoice->getId(),
            name: 'Test Product',
            quantity: 1,
            unitPrice: 1000
        );
        
        $invoice->addProductLine($productLine);
        $invoice->markAsSending();

        $invoice->markAsSentToClient();

        $this->assertEquals(StatusEnum::SentToClient, $invoice->getStatus());
        $this->assertTrue($invoice->isSentToClient());
    }

    public function testMarkAsSentToClientFailsWhenNotSending(): void
    {
        $this->expectException(InvalidInvoiceStatusException::class);

        $invoice = Invoice::create('John Doe', 'john@example.com');
        $invoice->markAsSentToClient(); // Should fail - not in Sending status
    }

    public function testUpdateCustomerDetails(): void
    {
        $invoice = Invoice::create('John Doe', 'john@example.com');

        $invoice->updateCustomerName('Jane Doe');
        $invoice->updateCustomerEmail('jane@example.com');

        $this->assertEquals('Jane Doe', $invoice->getCustomerName()->toString());
        $this->assertEquals('jane@example.com', $invoice->getCustomerEmail()->toString());
    }
}