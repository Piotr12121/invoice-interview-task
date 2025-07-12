<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CustomerEmailTest extends TestCase
{
    public function testValidEmail(): void
    {
        $email = new CustomerEmail('test@example.com');

        $this->assertEquals('test@example.com', $email->toString());
        $this->assertEquals('test@example.com', $email->value);
    }

    public function testEmptyEmailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer email cannot be empty.');

        new CustomerEmail('');
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer email must be a valid email address.');

        new CustomerEmail('invalid-email');
    }

    #[DataProvider('validEmailProvider')]
    public function testValidEmailFormats(string $email): void
    {
        $customerEmail = new CustomerEmail($email);
        $this->assertEquals($email, $customerEmail->toString());
    }

    /** @return array<array<string>> */
    public static function validEmailProvider(): array
    {
        return [
            ['user@example.com'],
            ['test.email@domain.co.uk'],
            ['user+tag@example.org'],
            ['firstname.lastname@company.com'],
        ];
    }

    #[DataProvider('invalidEmailProvider')]
    public function testInvalidEmailFormats(string $email): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new CustomerEmail($email);
    }

    /** @return array<array<string>> */
    public static function invalidEmailProvider(): array
    {
        return [
            ['plainaddress'],
            ['@missingdomain.com'],
            ['missing@.com'],
            ['spaces @example.com'],
            ['double@@example.com'],
        ];
    }
}