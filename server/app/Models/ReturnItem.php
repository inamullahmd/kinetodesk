<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_id',
        'sales_order_item_id',
        'qty',
        'item_condition',
        'action',
        'refund_amount',
        'notes',
    ];

    protected $casts = [
        'qty' => 'integer',
        'refund_amount' => 'decimal:2',
    ];

    public function returnRecord(): BelongsTo
    {
        return $this->belongsTo(ReturnRecord::class, 'return_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}
