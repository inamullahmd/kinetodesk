<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSupplierPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'supplier_sku',
        'cost_price',
        'currency_code',
        'minimum_order_qty',
        'effective_from',
        'effective_to',
        'is_primary',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'minimum_order_qty' => 'integer',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_primary' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}