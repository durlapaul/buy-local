<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating'     => 'required|integer|min:1|max:5',
        ]);

        abort_if($order->user_id !== $request->user()->id, 403);

        abort_if(!in_array($order->status, ['confirmed', 'shipped', 'delivered']), 422, 'Order is not eligible for review.');

        if ($order->status === 'delivered') {
            abort_if($order->completed_at->diffInDays(now()) > 5, 422, 'Review window has closed.');
        }

        abort_if(!$order->items->pluck('product_id')->contains($validated['product_id']), 422, 'Product does not belong to this order.');

        $review = ProductReview::updateOrCreate(
            [
                'reviewer_id' => $request->user()->id,
                'product_id'  => $validated['product_id'],
                'order_id'    => $order->id,
            ],
            [
                'rating'           => $validated['rating'],
                'is_auto_generated' => false,
            ]
        );

        return response()->json([
            'message' => 'Review submitted successfully.',
            'rating'  => $review->rating,
        ], 201);
    }

}
