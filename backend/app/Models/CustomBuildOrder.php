<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomBuildOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'build_template_id',
        'build_status',
        'assembly_notes',
        'labor_charge',
        'completed_at',
    ];

    protected $casts = [
        'labor_charge' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function buildTemplate(): BelongsTo
    {
        return $this->belongsTo(BuildTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomBuildOrderItem::class);
    }
}