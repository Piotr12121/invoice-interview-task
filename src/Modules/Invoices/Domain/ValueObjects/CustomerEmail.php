<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class CustomerEmail
{
    public function __construct(
        public string $value,
    ) {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Customer email cannot be empty.');
        }

        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Customer email must be a valid email address.');
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}