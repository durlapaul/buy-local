<?php

namespace App\Observers;

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
        Log::info('=== ProductObserver::created FIRED ===');
        Log::info('Product ID: ' . $product->id);
        Log::info('Unit Price: ' . $product->unit_price);


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
     * Handle the Product "updated" event.
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
