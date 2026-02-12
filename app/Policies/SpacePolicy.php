<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

class SpacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Space $space): bool
    {
        if($space->is_active) {
            return true;
        }

        return $user->canManageSpace($space);
    }

    public function create(User $user): bool
    {
        //TODO: We need to revise this after i create a subscritpion management
        return true;
    }

    public function update(User $user, Space $space): bool
    {
        return $user->isAdminOfSpace($space);
    }

    public function delete(User $user, Space $space): bool
    {
        return $space->owner_id === $user->id;
    }

    public function manageUsers(User $user, Space $space): bool
    {
        return $user->isAdminOfSpace($space);
    }

    public function createReservation(User $user, Space $space): bool
    {
        return $user->canManageSpace($space) || $user->isConsumer();
    }

    public function manageReservations(User $user, Space $space): bool
    {
        return $user->canManageSpace($space) && !$user->isConsumer();
    }
}