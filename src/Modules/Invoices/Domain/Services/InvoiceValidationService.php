<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Services;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;

final readonly class InvoiceValidationService
{
    public function canInvoiceBeCreated(string $customerName, string $customerEmail): bool
    {
        return !empty(trim($customerName)) && !empty(trim($customerEmail)) && 
               filter_var($customerEmail, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function canInvoiceBeSent(Invoice $invoice): bool
    {
        if ($invoice->getStatus() !== StatusEnum::Draft) {
            return false;
        }

        $productLines = $invoice->getProductLines();
        if (empty($productLines)) {
            return false;
        }

        foreach ($productLines as $productLine) {
            if (!$this->isProductLineValidForSending($productLine)) {
                return false;
            }
        }

        return true;
    }

    public function isProductLineValidForSending(InvoiceProductLine $productLine): bool
    {
        return $productLine->isValidForSending();
    }

    public function canStatusBeChangedTo(Invoice $invoice, StatusEnum $newStatus): bool
    {
        $currentStatus = $invoice->getStatus();

        return match ($newStatus) {
            StatusEnum::Draft => false, // Cannot change back to draft
            StatusEnum::Sending => $currentStatus === StatusEnum::Draft,
            StatusEnum::SentToClient => $currentStatus === StatusEnum::Sending,
        };
    }

    /** @param InvoiceProductLine[] $productLines */
    public function validateProductLinesForSending(array $productLines): array
    {
        $errors = [];

        if (empty($productLines)) {
            $errors[] = 'Invoice must contain at least one product line.';
        }

        foreach ($productLines as $index => $productLine) {
            if (!$productLine->getQuantity()->isPositive()) {
                $errors[] = "Product line {$index}: Quantity must be greater than zero.";
            }

            if (!$productLine->getUnitPrice()->isPositive()) {
                $errors[] = "Product line {$index}: Unit price must be greater than zero.";
            }

            if (empty(trim($productLine->getName()))) {
                $errors[] = "Product line {$index}: Product name cannot be empty.";
            }
        }

        return $errors;
    }
}