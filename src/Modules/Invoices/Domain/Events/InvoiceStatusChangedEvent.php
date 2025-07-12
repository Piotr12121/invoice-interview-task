<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Events;

use Modules\Invoices\Domain\Enums\StatusEnum;
use Ramsey\Uuid\UuidInterface;

final readonly class InvoiceStatusChangedEvent
{
    public function __construct(
        public UuidInterface $invoiceId,
        public StatusEnum $previousStatus,
        public StatusEnum $newStatus,
    ) {}
}