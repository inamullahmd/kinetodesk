<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomBuildOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_build_order_id',
        'component_type',
        'product_id',
        'serial_id',
        'quantity',
        'reserved_at',
        'consumed_at',
        'unit_cost_at_build',
        'unit_price_at_build',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_at' => 'datetime',
        'consumed_at' => 'datetime',
        'unit_cost_at_build' => 'decimal:2',
        'unit_price_at_build' => 'decimal:2',
    ];

    public function customBuildOrder(): BelongsTo
    {
        return $this->belongsTo(CustomBuildOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'serial_id');
    }
}