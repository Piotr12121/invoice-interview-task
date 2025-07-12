<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Invoices\Application\Dtos\CreateInvoiceDto;
use Modules\Invoices\Application\Dtos\InvoiceResponseDto;
use Modules\Invoices\Application\Dtos\ProductLineResponseDto;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Events\InvoiceCreatedEvent;
use Modules\Invoices\Domain\Events\InvoiceSentEvent;
use Modules\Invoices\Domain\Events\InvoiceStatusChangedEvent;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Domain\Repositories\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Services\InvoiceValidationService;
use Modules\Notifications\Api\Dtos\NotifyData;
use Modules\Notifications\Api\NotificationFacadeInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class InvoiceService
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceValidationService $validationService,
        private NotificationFacadeInterface $notificationFacade,
        private Dispatcher $eventDispatcher,
    ) {}

    public function createInvoice(CreateInvoiceDto $dto): InvoiceResponseDto
    {
        $invoice = Invoice::create(
            customerName: $dto->customerName,
            customerEmail: $dto->customerEmail,
        );

        foreach ($dto->productLines as $productLineDto) {
            $productLine = InvoiceProductLine::create(
                invoiceId: $invoice->getId(),
                name: $productLineDto->name,
                quantity: $productLineDto->quantity,
                unitPrice: $productLineDto->unitPrice,
            );
            $invoice->addProductLine($productLine);
        }

        $this->invoiceRepository->save($invoice);

        $this->eventDispatcher->dispatch(new InvoiceCreatedEvent(
            invoiceId: $invoice->getId(),
            customerName: $invoice->getCustomerName()->toString(),
            customerEmail: $invoice->getCustomerEmail()->toString(),
        ));

        return $this->toResponseDto($invoice);
    }

    public function getInvoice(UuidInterface $invoiceId): InvoiceResponseDto
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);
        return $this->toResponseDto($invoice);
    }

    public function sendInvoice(UuidInterface $invoiceId): InvoiceResponseDto
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if (!$this->validationService->canInvoiceBeSent($invoice)) {
            $validationErrors = $this->validationService->validateProductLinesForSending($invoice->getProductLines());
            throw new InvoiceCannotBeSentException(implode(' ', $validationErrors));
        }

        $previousStatus = $invoice->getStatus();
        $invoice->markAsSending();

        $this->invoiceRepository->save($invoice);

        $this->eventDispatcher->dispatch(new InvoiceStatusChangedEvent(
            invoiceId: $invoice->getId(),
            previousStatus: $previousStatus,
            newStatus: $invoice->getStatus(),
        ));

        // Send notification
        $notifyData = new NotifyData(
            resourceId: $invoice->getId(),
            toEmail: $invoice->getCustomerEmail()->toString(),
            subject: 'Your Invoice is Ready',
            message: "Dear {$invoice->getCustomerName()->toString()}, your invoice is ready for review. Total amount: {$invoice->getTotalPrice()->toInt()}.",
        );

        $this->notificationFacade->notify($notifyData);

        $this->eventDispatcher->dispatch(new InvoiceSentEvent(
            invoiceId: $invoice->getId(),
            customerEmail: $invoice->getCustomerEmail()->toString(),
        ));

        return $this->toResponseDto($invoice);
    }

    public function markInvoiceAsDelivered(UuidInterface $invoiceId): void
    {
        $invoice = $this->invoiceRepository->findByIdOrNull($invoiceId);
        
        if (!$invoice || !$invoice->isSending()) {
            return; // Ignore if invoice doesn't exist or not in sending status
        }

        $previousStatus = $invoice->getStatus();
        $invoice->markAsSentToClient();

        $this->invoiceRepository->save($invoice);

        $this->eventDispatcher->dispatch(new InvoiceStatusChangedEvent(
            invoiceId: $invoice->getId(),
            previousStatus: $previousStatus,
            newStatus: $invoice->getStatus(),
        ));
    }

    private function toResponseDto(Invoice $invoice): InvoiceResponseDto
    {
        $productLineResponses = [];
        foreach ($invoice->getProductLines() as $productLine) {
            $productLineResponses[] = new ProductLineResponseDto(
                id: $productLine->getId()->toString(),
                name: $productLine->getName(),
                quantity: $productLine->getQuantity()->toInt(),
                unitPrice: $productLine->getUnitPrice()->toInt(),
                totalUnitPrice: $productLine->getTotalPrice()->toInt(),
            );
        }

        return new InvoiceResponseDto(
            id: $invoice->getId()->toString(),
            customerName: $invoice->getCustomerName()->toString(),
            customerEmail: $invoice->getCustomerEmail()->toString(),
            status: $invoice->getStatus(),
            productLines: $productLineResponses,
            totalPrice: $invoice->getTotalPrice()->toInt(),
            createdAt: $invoice->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $invoice->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}