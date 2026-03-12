<?php

namespace App\Observers;

use App\Models\ProductReview;

class ProductReviewObserver
{
    /**
     * Handle the ProductReview "created" event.
     */
    public function created(ProductReview $productReview): void
    {
        $productReview->product->recalculateRating();
    }

    /**
     * Handle the ProductReview "updated" event.
     */
    public function updated(ProductReview $productReview): void
    {
        $productReview->product->recalculateRating();
    }

    /**
     * Handle the ProductReview "deleted" event.
     */
    public function deleted(ProductReview $productReview): void
    {
        $productReview->product->recalculateRating();
    }

    /**
     * Handle the ProductReview "restored" event.
     */
    public function restored(ProductReview $productReview): void
    {
        $productReview->product->recalculateRating();
    }

    /**
     * Handle the ProductReview "force deleted" event.
     */
    public function forceDeleted(ProductReview $productReview): void
    {
        //
    }
}
