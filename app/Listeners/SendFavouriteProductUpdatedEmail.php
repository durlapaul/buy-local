<?php

namespace App\Listeners;

use App\Events\FavouriteProductUpdated;
use App\Mail\FavouriteProductMail;
use Illuminate\Support\Facades\Mail;

class SendFavouriteProductUpdatedEmail
{
    public function handle(FavouriteProductUpdated $event): void
    {
        $product = $event->product;

        $product->favouritedBy()
            ->where('notify_favourites', true)
            ->each(function ($user) use ($product, $event) {
                Mail::to($user->email)->queue(
                    new FavouriteProductMail(
                        product: $product,
                        changeType: $event->changeType,
                    )
                );
            });
    }
}