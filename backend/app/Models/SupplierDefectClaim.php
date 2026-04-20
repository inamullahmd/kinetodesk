<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDefectClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'serial_id',
        'return_item_id',
        'claim_type',
        'status',
        'claim_date',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'claim_date' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'serial_id');
    }

    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(ReturnItem::class);
    }
}