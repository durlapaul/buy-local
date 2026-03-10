<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Listeners\SendOrderCancelledEmail;
use App\Listeners\SendOrderPlacedEmail;
use App\Listeners\SendOrderStatusEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
    ];
}
