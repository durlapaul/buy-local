<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProductReviewObserver;

#[ObservedBy([ProductReviewObserver::class])]
class ProductReview extends Model
{
    protected $fillable = [
        'product_id',
        'reviewer_id',
        'order_id',
        'rating',
        'is_auto_generated',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_auto_generated' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isWithinEditWindow(): bool
    {
        return $this->order->completed_at->diffInDays(now()) < 5;
    }
}
