<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderStatusMail;

class SendOrderCancelledEmail
{
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order->load('items.seller');

        $order->items
            ->pluck('seller')
            ->unique('id')
            ->each(function ($seller) use ($order) {
                if (!$seller->notify_order_updates) return;

                Mail::to($seller->email)->queue(
                    new OrderStatusMail(
                        order: $order,
                        status: 'cancelled',
                        reason: $order->cancel_reason,
                        role: 'seller'
                    )
                );
            });
    }
}

