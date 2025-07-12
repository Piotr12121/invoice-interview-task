<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

final readonly class CreateInvoiceDto
{
    /** @param ProductLineDto[] $productLines */
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public array $productLines = [],
    ) {}
}