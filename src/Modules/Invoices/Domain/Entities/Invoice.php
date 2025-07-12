<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Entities;

use DateTimeImmutable;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStatusException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\Money;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Invoice
{
    /** @var InvoiceProductLine[] */
    private array $productLines = [];

    public function __construct(
        private UuidInterface $id,
        private CustomerName $customerName,
        private CustomerEmail $customerEmail,
        private StatusEnum $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $customerName,
        string $customerEmail,
    ): self {
        $now = new DateTimeImmutable();
        
        return new self(
            id: Uuid::uuid4(),
            customerName: new CustomerName($customerName),
            customerEmail: new CustomerEmail($customerEmail),
            status: StatusEnum::Draft,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCustomerName(): CustomerName
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): CustomerEmail
    {
        return $this->customerEmail;
    }

    public function getStatus(): StatusEnum
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return InvoiceProductLine[] */
    public function getProductLines(): array
    {
        return $this->productLines;
    }

    public function addProductLine(InvoiceProductLine $productLine): void
    {
        $this->productLines[] = $productLine;
        $this->updatedAt = new DateTimeImmutable();
    }

    /** @param InvoiceProductLine[] $productLines */
    public function setProductLines(array $productLines): void
    {
        $this->productLines = $productLines;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getTotalPrice(): Money
    {
        $total = Money::zero();
        
        foreach ($this->productLines as $productLine) {
            $total = $total->add($productLine->getTotalPrice());
        }
        
        return $total;
    }

    public function canBeSent(): bool
    {
        if ($this->status !== StatusEnum::Draft) {
            return false;
        }

        if (empty($this->productLines)) {
            return false;
        }

        foreach ($this->productLines as $productLine) {
            if (!$productLine->isValidForSending()) {
                return false;
            }
        }

        return true;
    }

    public function markAsSending(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw new InvalidInvoiceStatusException($this->status, StatusEnum::Sending);
        }

        if (!$this->canBeSent()) {
            throw new InvoiceCannotBeSentException(
                'Invoice must contain product lines with positive quantity and unit price.'
            );
        }

        $this->status = StatusEnum::Sending;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsSentToClient(): void
    {
        if ($this->status !== StatusEnum::Sending) {
            throw new InvalidInvoiceStatusException($this->status, StatusEnum::SentToClient);
        }

        $this->status = StatusEnum::SentToClient;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isDraft(): bool
    {
        return $this->status === StatusEnum::Draft;
    }

    public function isSending(): bool
    {
        return $this->status === StatusEnum::Sending;
    }

    public function isSentToClient(): bool
    {
        return $this->status === StatusEnum::SentToClient;
    }

    public function updateCustomerName(string $customerName): void
    {
        $this->customerName = new CustomerName($customerName);
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = new CustomerEmail($customerEmail);
        $this->updatedAt = new DateTimeImmutable();
    }
}