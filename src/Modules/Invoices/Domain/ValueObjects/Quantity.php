<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Quantity
{
    public function __construct(
        public int $value,
    ) {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }
    }

    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    public function toInt(): int
    {
        return $this->value;
    }
}