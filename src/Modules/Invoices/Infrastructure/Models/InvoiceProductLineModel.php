<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InvoiceProductLineModel extends Model
{
    use HasUuids;

    protected $table = 'invoice_product_lines';

    protected $fillable = [
        'id',
        'invoice_id',
        'name',
        'price',
        'quantity',
    ];

    protected $casts = [
        'id' => 'string',
        'invoice_id' => 'string',
        'price' => 'integer',
        'quantity' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }
}