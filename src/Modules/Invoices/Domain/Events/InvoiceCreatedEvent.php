<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Events;

use Ramsey\Uuid\UuidInterface;

final readonly class InvoiceCreatedEvent
{
    public function __construct(
        public UuidInterface $invoiceId,
        public string $customerName,
        public string $customerEmail,
    ) {}
}