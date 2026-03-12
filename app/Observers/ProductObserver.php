<?php

namespace App\Observers;

use App\Events\FavouriteProductUpdated;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        ProductPriceHistory::create([
            'product_id' => $product->id,
            'unit_price' => $product->unit_price,
            'currency' => $product->currency,
            'effective_from' => now(),
            'changed_by_type' => Auth::check() ? 'User' : 'System',
            'changed_by_id' => Auth::id(),
            'reason' => 'Initial price',
        ]);
    }

    /**
     * Handle the Product "updating" event.
     */
    public function updating(Product $product): void
    {
        if ($product->isDirty('unit_price')) {
            $oldPrice = $product->getOriginal('unit_price');
            $newPrice = $product->unit_price;

            if ($oldPrice == $newPrice) {
                return;
            }

            ProductPriceHistory::where('product_id', $product->id)
                ->whereNull('effective_to')
                ->update(['effective_to' => now()]);

            ProductPriceHistory::create([
                'product_id' => $product->id,
                'unit_price' => $newPrice,
                'currency' => $product->currency,
                'effective_from' => now(),
                'changed_by_type' => Auth::check() ? 'User' : 'System',
                'changed_by_id' => Auth::id(),
                'reason' => $product->price_change_reason ?? 'Price updated',
            ]);

            unset($product->price_change_reason);
        }
    }

    public function updated(Product $product): void
    {
        if ($product->status !== 'available') {
            return;
        }

        $changes = collect($product->getChanges())
            ->except(['updated_at'])
            ->map(fn($value, $key) => match($key) {
                'unit_price' => "Price updated to {$product->unit_price} {$product->currency}",
                'name'       => "Name changed to {$value}",
                'status'     => "Product is now {$value}",
                default      => null,
            })
            ->filter()
            ->join(', ');

        if (!$changes) {
            return;
        }

        FavouriteProductUpdated::dispatch($product, $changes);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
