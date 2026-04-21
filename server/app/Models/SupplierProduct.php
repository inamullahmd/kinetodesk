<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    protected $fillable = [
        'supplier_id',
        'product_id',
        'supplier_sku',
        'preferred_supplier',
        'min_order_qty',
        'lead_time_days',
        'last_cost',
        'currency',
        'is_active'
    ];

    protected $casts = [
        'preferred_supplier' => 'boolean',
        'is_active' => 'boolean',
        'last_cost' => 'decimal:2',
        'min_order_qty' => 'integer',
        'lead_time_days' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
