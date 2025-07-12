<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\ValueObjects;

final readonly class Money
{
    public function __construct(
        public int $amount,
    ) {}

    public function add(Money $other): Money
    {
        return new Money($this->amount + $other->amount);
    }

    public function multiply(int $multiplier): Money
    {
        return new Money($this->amount * $multiplier);
    }

    public function toInt(): int
    {
        return $this->amount;
    }

    public static function zero(): Money
    {
        return new Money(0);
    }
}