<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'status',
        'product_category_id',
        'unit_of_measurement',
        'user_id',
        'currency'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getPriceAt(\DateTime $date): ?float
    {
        $history = $this->priceHistory()
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $date);
            })
            ->first();

        return $history?->price;
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
         if (empty($search)) {
            return $query;
        }

        return $query->whereRaw(
            'MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$search]
        );
    }

    public function scopeLocation(Builder $query, string $location): Builder
    {
        return $query->whereHas('seller', function (Builder $q) use ($location) {
            $q->where('city', 'like', "%{$location}%");
        });
    }
}
