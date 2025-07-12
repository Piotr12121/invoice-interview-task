<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

final readonly class ProductLineResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public int $quantity,
        public int $unitPrice,
        public int $totalUnitPrice,
    ) {}
}