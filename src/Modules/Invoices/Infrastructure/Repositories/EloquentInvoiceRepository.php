<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use DateTimeImmutable;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use Modules\Invoices\Infrastructure\Models\InvoiceModel;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function save(Invoice $invoice): void
    {
        $data = [
            'id' => $invoice->getId()->toString(),
            'customer_name' => $invoice->getCustomerName()->toString(),
            'customer_email' => $invoice->getCustomerEmail()->toString(),
            'status' => $invoice->getStatus()->value,
        ];

        $model = InvoiceModel::query()->find($invoice->getId()->toString());
        
        if ($model) {
            $model->update($data);
        } else {
            $model = InvoiceModel::query()->create($data);
        }

        // Save product lines
        $model->productLines()->delete();
        
        foreach ($invoice->getProductLines() as $productLine) {
            $model->productLines()->create([
                'id' => $productLine->getId()->toString(),
                'name' => $productLine->getName(),
                'price' => $productLine->getUnitPrice()->toInt(),
                'quantity' => $productLine->getQuantity()->toInt(),
            ]);
        }
    }

    public function findById(UuidInterface $id): Invoice
    {
        $model = InvoiceModel::query()
            ->with('productLines')
            ->where('id', $id->toString())
            ->first();

        if (!$model) {
            throw new InvoiceNotFoundException($id->toString());
        }

        return $this->toDomainEntity($model);
    }

    public function findByIdOrNull(UuidInterface $id): ?Invoice
    {
        try {
            return $this->findById($id);
        } catch (InvoiceNotFoundException) {
            return null;
        }
    }

    public function exists(UuidInterface $id): bool
    {
        return InvoiceModel::query()
            ->where('id', $id->toString())
            ->exists();
    }

    private function toDomainEntity(InvoiceModel $model): Invoice
    {
        $productLines = [];
        foreach ($model->productLines as $productLineModel) {
            $productLines[] = new InvoiceProductLine(
                id: Uuid::fromString($productLineModel->id),
                invoiceId: Uuid::fromString($productLineModel->invoice_id),
                name: $productLineModel->name,
                quantity: new Quantity($productLineModel->quantity),
                unitPrice: new UnitPrice($productLineModel->price)
            );
        }

        $invoice = new Invoice(
            id: Uuid::fromString($model->id),
            customerName: new CustomerName($model->customer_name),
            customerEmail: new CustomerEmail($model->customer_email),
            status: StatusEnum::from($model->status),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );

        if (!empty($productLines)) {
            $invoice->setProductLines($productLines);
        }

        return $invoice;
    }

}