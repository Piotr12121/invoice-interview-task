<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Repositories;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Ramsey\Uuid\UuidInterface;

interface InvoiceProductLineRepositoryInterface
{
    public function save(InvoiceProductLine $productLine): void;

    /** @return InvoiceProductLine[] */
    public function findByInvoiceId(UuidInterface $invoiceId): array;

    public function deleteByInvoiceId(UuidInterface $invoiceId): void;
}