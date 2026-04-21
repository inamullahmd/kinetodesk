<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesItemAllocation extends Model
{
    protected $fillable = [
        'sales_order_item_id',
        'stock_batch_id',
        'qty_allocated',
        'unit_cost',
    ];

    protected $casts = [
        'qty_allocated' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }
}