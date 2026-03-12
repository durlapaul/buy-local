<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Console\Command;

class AutoGenerateProductReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-generate-product-reviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate 5-star reviews for orders delivered more than 5 days ago with no review';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Order::query()
            ->where('status', 'delivered')
            ->where('completed_at', '<=', now()->subDays(5))
            ->with('items')
            ->each(function (Order $order) {
                $order->items->each(function ($item) use ($order) {
                    $alreadyReviewed = ProductReview::where([
                        'reviewer_id' => $order->user_id,
                        'product_id'  => $item->product_id,
                        'order_id'    => $order->id,
                    ])->exists();

                    if ($alreadyReviewed) return;

                    ProductReview::create([
                        'reviewer_id'      => $order->user_id,
                        'product_id'       => $item->product_id,
                        'order_id'         => $order->id,
                        'rating'           => 5,
                        'is_auto_generated' => true,
                    ]);
                });
            });

        $this->info('Auto-generated reviews complete.');
    }
}
