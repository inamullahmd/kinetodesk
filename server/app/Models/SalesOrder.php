<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'customer_id',
        'employee_id',
        'so_number',
        'channel',
        'status',
        'payment_status',
        'fulfillment_status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_fee',
        'grand_total',
        'contact_name',
        'contact_phone',
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_city',
        'delivery_state',
        'delivery_postal_code',
        'delivery_country',
        'notes',
        'ordered_at',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returnRecords(): HasMany
    {
        return $this->hasMany(ReturnRecord::class, 'sales_order_id');
    }
}