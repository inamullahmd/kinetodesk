<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'po_number',
        'status',
        'ordered_at',
        'expected_at',
        'received_at',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'other_cost',
        'total_cost',
        'notes'
    ];

    protected $casts = [
        'ordered_at' => 'date',
        'expected_at' => 'date',
        'received_at' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
