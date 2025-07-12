<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Repositories;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Ramsey\Uuid\UuidInterface;

interface InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void;

    /**
     * @throws InvoiceNotFoundException
     */
    public function findById(UuidInterface $id): Invoice;

    public function findByIdOrNull(UuidInterface $id): ?Invoice;

    public function exists(UuidInterface $id): bool;
}