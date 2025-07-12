<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CustomerName
{
    public function __construct(
        public string $value,
    ) {
        if (empty(trim($this->value))) {
            throw new InvalidArgumentException('Customer name cannot be empty.');
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}