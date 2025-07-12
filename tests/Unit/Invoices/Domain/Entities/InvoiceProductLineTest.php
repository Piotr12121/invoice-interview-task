<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class InvoiceProductLineTest extends TestCase
{
    public function testCreateProductLine(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 2,
            unitPrice: 1000
        );

        $this->assertInstanceOf(InvoiceProductLine::class, $productLine);
        $this->assertEquals($invoiceId->toString(), $productLine->getInvoiceId()->toString());
        $this->assertEquals('Test Product', $productLine->getName());
        $this->assertEquals(2, $productLine->getQuantity()->toInt());
        $this->assertEquals(1000, $productLine->getUnitPrice()->toInt());
    }

    public function testGetTotalPrice(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 3,
            unitPrice: 1500
        );

        $this->assertEquals(4500, $productLine->getTotalPrice()->toInt()); // 3 * 1500
    }

    public function testIsValidForSendingWithPositiveValues(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 1,
            unitPrice: 100
        );

        $this->assertTrue($productLine->isValidForSending());
    }

    public function testIsNotValidForSendingWithZeroQuantity(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 0,
            unitPrice: 100
        );

        $this->assertFalse($productLine->isValidForSending());
    }

    public function testIsNotValidForSendingWithZeroUnitPrice(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 1,
            unitPrice: 0
        );

        $this->assertFalse($productLine->isValidForSending());
    }

    public function testUpdateQuantity(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 1,
            unitPrice: 100
        );

        $productLine->updateQuantity(5);

        $this->assertEquals(5, $productLine->getQuantity()->toInt());
        $this->assertEquals(500, $productLine->getTotalPrice()->toInt()); // 5 * 100
    }

    public function testUpdateUnitPrice(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 2,
            unitPrice: 100
        );

        $productLine->updateUnitPrice(200);

        $this->assertEquals(200, $productLine->getUnitPrice()->toInt());
        $this->assertEquals(400, $productLine->getTotalPrice()->toInt()); // 2 * 200
    }

    public function testUpdateName(): void
    {
        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 1,
            unitPrice: 100
        );

        $productLine->updateName('Updated Product');

        $this->assertEquals('Updated Product', $productLine->getName());
    }

    public function testCreateWithEmptyNameThrowsException(): void
    {
        $this->expectException(InvalidProductLineException::class);
        $this->expectExceptionMessage('Product name cannot be empty.');

        $invoiceId = Uuid::uuid4();
        InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: '',
            quantity: 1,
            unitPrice: 100
        );
    }

    public function testUpdateWithEmptyNameThrowsException(): void
    {
        $this->expectException(InvalidProductLineException::class);
        $this->expectExceptionMessage('Product name cannot be empty.');

        $invoiceId = Uuid::uuid4();
        $productLine = InvoiceProductLine::create(
            invoiceId: $invoiceId,
            name: 'Test Product',
            quantity: 1,
            unitPrice: 100
        );

        $productLine->updateName('   '); // Whitespace only
    }
}