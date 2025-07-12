<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Services;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\ValueObjects\Money;

final readonly class InvoiceCalculationService
{
    public function calculateProductLineTotalPrice(InvoiceProductLine $productLine): Money
    {
        return $productLine->getTotalPrice();
    }

    public function calculateInvoiceTotalPrice(Invoice $invoice): Money
    {
        return $invoice->getTotalPrice();
    }

    /** @param InvoiceProductLine[] $productLines */
    public function calculateTotalForProductLines(array $productLines): Money
    {
        $total = Money::zero();
        
        foreach ($productLines as $productLine) {
            $total = $total->add($productLine->getTotalPrice());
        }
        
        return $total;
    }
}