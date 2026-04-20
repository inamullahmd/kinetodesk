<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'default_lead_time_days',
        'rating',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'default_lead_time_days' => 'integer',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function preferredProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'preferred_supplier_id');
    }

    public function supplierPrices(): HasMany
    {
        return $this->hasMany(ProductSupplierPrice::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function defectClaims(): HasMany
    {
        return $this->hasMany(SupplierDefectClaim::class);
    }

    public function reorderAlerts(): HasMany
    {
        return $this->hasMany(ReorderAlert::class);
    }
}