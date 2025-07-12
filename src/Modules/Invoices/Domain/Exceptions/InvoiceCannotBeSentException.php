<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Exception;

final class InvoiceCannotBeSentException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct("Invoice cannot be sent: {$reason}");
    }
}