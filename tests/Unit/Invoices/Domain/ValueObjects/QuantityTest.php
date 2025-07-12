<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function testValidPositiveQuantity(): void
    {
        $quantity = new Quantity(5);

        $this->assertEquals(5, $quantity->toInt());
        $this->assertTrue($quantity->isPositive());
    }

    public function testZeroQuantity(): void
    {
        $quantity = new Quantity(0);

        $this->assertEquals(0, $quantity->toInt());
        $this->assertFalse($quantity->isPositive());
    }

    public function testNegativeQuantityThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity cannot be negative.');

        new Quantity(-1);
    }

    #[DataProvider('positiveQuantityProvider')]
    public function testPositiveQuantities(int $value): void
    {
        $quantity = new Quantity($value);
        
        $this->assertEquals($value, $quantity->toInt());
        $this->assertTrue($quantity->isPositive());
    }

    /** @return array<array<int>> */
    public static function positiveQuantityProvider(): array
    {
        return [
            [1],
            [10],
            [100],
            [999],
        ];
    }

    #[DataProvider('negativeQuantityProvider')]
    public function testNegativeQuantities(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Quantity($value);
    }

    /** @return array<array<int>> */
    public static function negativeQuantityProvider(): array
    {
        return [
            [-1],
            [-10],
            [-100],
        ];
    }
}