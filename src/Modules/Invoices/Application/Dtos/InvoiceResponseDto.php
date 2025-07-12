<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

use Modules\Invoices\Domain\Enums\StatusEnum;

final readonly class InvoiceResponseDto
{
    /** @param ProductLineResponseDto[] $productLines */
    public function __construct(
        public string $id,
        public string $customerName,
        public string $customerEmail,
        public StatusEnum $status,
        public array $productLines,
        public int $totalPrice,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}