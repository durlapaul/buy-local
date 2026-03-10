<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderStatusMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;


class SendOrderPlacedEmail
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('items.seller');
        
        $order->items
            ->pluck('seller')
            ->unique('id')
            ->filter(fn($seller) => $seller->notify_order_updates)
            ->each(fn($seller) => Mail::to($seller->email)->queue(
                new OrderStatusMail(
                    order: $order,
                    status: 'placed',
                    reason: null,
                    role: 'seller'
                )
            ));
    }
}
