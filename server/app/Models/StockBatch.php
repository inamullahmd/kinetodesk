<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id',
        'purchase_order_item_id',
        'batch_code',
        'qty_received',
        'qty_remaining',
        'unit_cost',
        'received_at'
    ];

    protected $casts = [
        'qty_received' => 'integer',
        'qty_remaining' => 'integer',
        'unit_cost' => 'decimal:2',
        'received_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function salesItemAssociations(): HasMany
    {
        return $this->hasMany(SalesItemAllocation::class);
    }
}
