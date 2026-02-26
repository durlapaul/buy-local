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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[ObservedBy([ProductObserver::class])]
class Product extends Model implements HasMedia
{
    use SoftDeletes, HasFactory, InteractsWithMedia;

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

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
            ->useDisk('public');
    }

    /**
     * Register media conversions (thumbnails, etc.)
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(800)
            ->sharpen(10)
            ->nonQueued();
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('images');
    }

    public function getPrimaryImageThumbAttribute(): ?string
    {
        return $this->getFirstMediaUrl('images', 'thumb');
    }

    public function getImageUrlsAttribute(): array
    {
        return $this->getMedia('images')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'preview' => $media->getUrl('preview'),
                'name' => $media->file_name,
                'size' => $media->size,
                'order' => $media->order_column,
            ];
        })->toArray();
    }

    public function hasImages(): bool
    {
        return $this->getMedia('images')->isNotEmpty();
    }
}
