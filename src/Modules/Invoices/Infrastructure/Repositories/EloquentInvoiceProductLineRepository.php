<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Repositories\InvoiceProductLineRepositoryInterface;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use Modules\Invoices\Infrastructure\Models\InvoiceProductLineModel;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class EloquentInvoiceProductLineRepository implements InvoiceProductLineRepositoryInterface
{
    public function save(InvoiceProductLine $productLine): void
    {
        InvoiceProductLineModel::query()->updateOrCreate(
            ['id' => $productLine->getId()->toString()],
            [
                'invoice_id' => $productLine->getInvoiceId()->toString(),
                'name' => $productLine->getName(),
                'price' => $productLine->getUnitPrice()->toInt(),
                'quantity' => $productLine->getQuantity()->toInt(),
            ]
        );
    }

    public function findByInvoiceId(UuidInterface $invoiceId): array
    {
        $models = InvoiceProductLineModel::query()
            ->where('invoice_id', $invoiceId->toString())
            ->get();

        $productLines = [];
        foreach ($models as $model) {
            $productLines[] = new InvoiceProductLine(
                id: Uuid::fromString($model->id),
                invoiceId: Uuid::fromString($model->invoice_id),
                name: $model->name,
                quantity: new Quantity($model->quantity),
                unitPrice: new UnitPrice($model->price),
            );
        }

        return $productLines;
    }

    public function deleteByInvoiceId(UuidInterface $invoiceId): void
    {
        InvoiceProductLineModel::query()
            ->where('invoice_id', $invoiceId->toString())
            ->delete();
    }
}