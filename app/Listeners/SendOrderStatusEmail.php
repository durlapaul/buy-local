<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Mail\OrderStatusMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusEmail
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order->load('buyer');
        $buyer = $order->buyer;

        if(!$buyer->notify_order_updates) return;

        Mail::to($buyer->email)->queue(
            new OrderStatusMail(
                order: $order,
                status: $event->status,
                reason: $event->rejection_reason,
                role: 'buyer'
            )
        );
    }
}
