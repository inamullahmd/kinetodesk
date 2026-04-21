<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'stock_batch_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'qty_change',
        'unit_cost',
        'notes',
        'moved_at',
    ];

    protected $casts = [
        'qty_change' => 'integer',
        'unit_cost' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }
}
