<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'brand_id',
        'product_type',
        'is_serialized',
        'unit_of_measure',
        'reorder_threshold',
        'preferred_supplier_id',
        'current_stock',
        'reserved_stock',
        'sell_price',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_serialized' => 'boolean',
        'reorder_threshold' => 'integer',
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'sell_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'available_stock',
    ];

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) $this->current_stock - (int) $this->reserved_stock);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function supplierPrices(): HasMany
    {
        return $this->hasMany(ProductSupplierPrice::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function serviceTicketParts(): HasMany
    {
        return $this->hasMany(ServiceTicketPart::class);
    }

    public function buildTemplateItems(): HasMany
    {
        return $this->hasMany(BuildTemplateItem::class);
    }

    public function customBuildOrderItems(): HasMany
    {
        return $this->hasMany(CustomBuildOrderItem::class);
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