<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoices\Application\Dtos\ProductLineResponseDto;

final class InvoiceProductLineResource extends JsonResource
{
    public function __construct(
        private ProductLineResponseDto $productLineDto,
    ) {
        parent::__construct($productLineDto);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->productLineDto->id,
            'name' => $this->productLineDto->name,
            'quantity' => $this->productLineDto->quantity,
            'unit_price' => $this->productLineDto->unitPrice,
            'total_unit_price' => $this->productLineDto->totalUnitPrice,
        ];
    }
}