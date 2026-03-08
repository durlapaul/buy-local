<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders.seller.{sellerId}', function ($user, $sellerId) {
    return (int) $user->id === (int) $sellerId;
});

Broadcast::channel('orders.buyer.{buyerId}', function ($user, $buyerId) {
    return (int) $user->id === (int) $buyerId;
});