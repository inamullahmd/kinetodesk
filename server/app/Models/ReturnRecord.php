<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRecord extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'sales_order_id',
        'customer_id',
        'return_number',
        'status',
        'reason',
        'refund_amount',
        'restock',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'restock' => 'boolean',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}