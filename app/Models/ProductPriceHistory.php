<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    protected $fillable = [
        'product_id',
        'unit_price',
        'currency',
        'effective_from',
        'effective_to',
        'changed_by_type',
        'changed_by_id',
        'reason',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
