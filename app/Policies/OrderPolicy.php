<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($order->user_id == $user->id) {
            return true;
        }

        return $order->items()
            ->where('seller_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id
            && $order->status === 'pending';
    }

    public function reject(User $user, Order $order): bool
    {
        return $order->status === 'pending'
            && $order->items()
                ->where('seller_id', $user->id)
                ->exists();
    }

    public function confirm(User $user, Order $order): bool
    {
        return $order->status === 'pending'
            && $order->items()
                ->where('seller_id', $user->id)
                ->exists();
    }

    public function ship(User $user, Order $order): bool
    {
        return $order->status === 'confirmed'
            && $order->items()
                ->where('seller_id', $user->id)
                ->exists();
    }

    public function deliver(User $user, Order $order): bool
    {
        return $order->status === 'shipped'
            && $order->items()
                ->where('seller_id', $user->id)
                ->exists();
    }

    public function canModerate(User $user): bool
    {
        return $user->isAdmin();
    }
}
