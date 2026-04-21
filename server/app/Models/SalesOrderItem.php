<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'employee_id',
        'qty',
        'unit_price',
        'discount_amount',
        'final_unit_price',
        'cost_basis',
        'min_allowed_price',
        'commission_per_unit',
        'commission_total',
        'line_subtotal',
        'line_total',
        'line_profit',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_unit_price' => 'decimal:2',
        'cost_basis' => 'decimal:2',
        'min_allowed_price' => 'decimal:2',
        'commission_per_unit' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_profit' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SalesItemAllocation::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }
}
