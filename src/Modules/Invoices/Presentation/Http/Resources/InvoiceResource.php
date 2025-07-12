<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoices\Application\Dtos\InvoiceResponseDto;

final class InvoiceResource extends JsonResource
{
    public function __construct(
        private InvoiceResponseDto $invoiceDto,
    ) {
        parent::__construct($invoiceDto);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->invoiceDto->id,
            'customer_name' => $this->invoiceDto->customerName,
            'customer_email' => $this->invoiceDto->customerEmail,
            'status' => $this->invoiceDto->status->value,
            'product_lines' => InvoiceProductLineResource::collection($this->invoiceDto->productLines),
            'total_price' => $this->invoiceDto->totalPrice,
            'created_at' => $this->invoiceDto->createdAt,
            'updated_at' => $this->invoiceDto->updatedAt,
        ];
    }
}