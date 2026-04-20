<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'build_template_id',
        'component_type',
        'product_id',
        'quantity',
        'is_required',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_required' => 'boolean',
    ];

    public function buildTemplate(): BelongsTo
    {
        return $this->belongsTo(BuildTemplate::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}