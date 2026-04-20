<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'current_stock',
        'reorder_threshold',
        'suggested_quantity',
        'status',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'reorder_threshold' => 'integer',
        'suggested_quantity' => 'integer',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}