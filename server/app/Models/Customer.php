<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'customer_type',
        'first_name',
        'last_name',
        'business_name',
        'email',
        'phone',
        'tax_number',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function returnRecords(): HasMany
    {
        return $this->hasMany(ReturnRecord::class, 'customer_id');
    }

    public function getFullNameAttribute(): string
    {
        if ($this->customer_type === 'business') {
            return $this->business_name ?? '';
        }
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getShortNameAttribute(): string
    {
        if ($this->customer_type === 'business') {
            return $this->business_name ?? '';
        }
        return "{$this->first_name} {$this->last_name[0]}." ?? '';
    }

}
