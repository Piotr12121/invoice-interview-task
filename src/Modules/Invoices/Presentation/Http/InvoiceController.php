<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Invoices\Application\Dtos\CreateInvoiceDto;
use Modules\Invoices\Application\Dtos\ProductLineDto;
use Modules\Invoices\Application\Services\InvoiceService;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Presentation\Http\Requests\CreateInvoiceRequest;
use Modules\Invoices\Presentation\Http\Requests\SendInvoiceRequest;
use Modules\Invoices\Presentation\Http\Resources\InvoiceResource;
use Ramsey\Uuid\Uuid;
use Throwable;

final readonly class InvoiceController
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function show(string $id): JsonResponse
    {
        try {
            $invoiceId = Uuid::fromString($id);
            $invoice = $this->invoiceService->getInvoice($invoiceId);
            
            return response()->json([
                'data' => new InvoiceResource($invoice),
            ]);
        } catch (InvoiceNotFoundException $e) {
            return response()->json([
                'error' => 'Invoice not found',
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred while retrieving the invoice',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        try {
            $productLines = [];
            if ($request->has('product_lines')) {
                foreach ($request->input('product_lines', []) as $productLineData) {
                    $productLines[] = new ProductLineDto(
                        name: $productLineData['name'],
                        quantity: (int) $productLineData['quantity'],
                        unitPrice: (int) $productLineData['unit_price'],
                    );
                }
            }

            $createDto = new CreateInvoiceDto(
                customerName: $request->input('customer_name'),
                customerEmail: $request->input('customer_email'),
                productLines: $productLines,
            );

            $invoice = $this->invoiceService->createInvoice($createDto);

            return response()->json([
                'data' => new InvoiceResource($invoice),
                'message' => 'Invoice created successfully',
            ], Response::HTTP_CREATED);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred while creating the invoice',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function send(string $id, SendInvoiceRequest $request): JsonResponse
    {
        try {
            $invoiceId = Uuid::fromString($id);
            $invoice = $this->invoiceService->sendInvoice($invoiceId);

            return response()->json([
                'data' => new InvoiceResource($invoice),
                'message' => 'Invoice sent successfully',
            ]);
        } catch (InvoiceNotFoundException $e) {
            return response()->json([
                'error' => 'Invoice not found',
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (InvoiceCannotBeSentException $e) {
            return response()->json([
                'error' => 'Invoice cannot be sent',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'An error occurred while sending the invoice',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}