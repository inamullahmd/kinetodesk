<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_order_item_id',
        'serial_number',
        'status',
        'warranty_expiry_date',
    ];

    protected $casts = [
        'warranty_expiry_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'serial_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'serial_id');
    }

    public function serviceTickets(): HasMany
    {
        return $this->hasMany(ServiceTicket::class, 'related_serial_id');
    }

    public function serviceTicketParts(): HasMany
    {
        return $this->hasMany(ServiceTicketPart::class, 'serial_id');
    }

    public function customBuildOrderItems(): HasMany
    {
        return $this->hasMany(CustomBuildOrderItem::class, 'serial_id');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'serial_id');
    }

    public function defectClaims(): HasMany
    {
        return $this->hasMany(SupplierDefectClaim::class, 'serial_id');
    }
}