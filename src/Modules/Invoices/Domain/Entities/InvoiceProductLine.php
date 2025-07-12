<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use Modules\Invoices\Domain\Exceptions\InvalidProductLineException;
use Modules\Invoices\Domain\ValueObjects\Money;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class InvoiceProductLine
{
    public function __construct(
        private UuidInterface $id,
        private UuidInterface $invoiceId,
        private string $name,
        private Quantity $quantity,
        private UnitPrice $unitPrice,
    ) {
        if (empty(trim($this->name))) {
            throw new InvalidProductLineException('Product name cannot be empty.');
        }
    }

    public static function create(
        UuidInterface $invoiceId,
        string $name,
        int $quantity,
        int $unitPrice,
    ): self {
        return new self(
            id: Uuid::uuid4(),
            invoiceId: $invoiceId,
            name: $name,
            quantity: new Quantity($quantity),
            unitPrice: new UnitPrice($unitPrice),
        );
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getInvoiceId(): UuidInterface
    {
        return $this->invoiceId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getUnitPrice(): UnitPrice
    {
        return $this->unitPrice;
    }

    public function getTotalPrice(): Money
    {
        return new Money($this->quantity->toInt() * $this->unitPrice->toInt());
    }

    public function isValidForSending(): bool
    {
        return $this->quantity->isPositive() && $this->unitPrice->isPositive();
    }

    public function updateQuantity(int $quantity): void
    {
        $this->quantity = new Quantity($quantity);
    }

    public function updateUnitPrice(int $unitPrice): void
    {
        $this->unitPrice = new UnitPrice($unitPrice);
    }

    public function updateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidProductLineException('Product name cannot be empty.');
        }
        $this->name = $name;
    }
}