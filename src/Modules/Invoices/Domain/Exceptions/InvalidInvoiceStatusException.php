<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Exception;
use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvalidInvoiceStatusException extends Exception
{
    public function __construct(StatusEnum $currentStatus, StatusEnum $attemptedStatus)
    {
        parent::__construct(
            "Cannot change invoice status from '{$currentStatus->value}' to '{$attemptedStatus->value}'."
        );
    }
}