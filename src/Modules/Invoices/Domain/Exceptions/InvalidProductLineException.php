<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Exception;

final class InvalidProductLineException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct("Invalid product line: {$reason}");
    }
}