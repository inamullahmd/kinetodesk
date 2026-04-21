<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'internal_sku',
        'model_number',
        'title',
        'description',
        'commission_value',
        'is_serialized',
        'is_active'
    ];

    protected $casts = [
        'is_serialized' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function productSpecs(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class);
    }
}
