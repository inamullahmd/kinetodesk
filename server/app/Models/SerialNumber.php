<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SerialNumber extends Model
{
    protected $fillable = [
        'product_id',
        'stock_batch_id',
        'serial_number',
        'status',
    ];

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
