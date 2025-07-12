<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Exception;

final class InvoiceNotFoundException extends Exception
{
    public function __construct(string $invoiceId)
    {
        parent::__construct("Invoice with ID '{$invoiceId}' not found.");
    }
}