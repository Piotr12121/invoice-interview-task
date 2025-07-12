<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Dtos;

final readonly class ProductLineDto
{
    public function __construct(
        public string $name,
        public int $quantity,
        public int $unitPrice,
    ) {}
}