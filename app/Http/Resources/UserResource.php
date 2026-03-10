<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'email_verified_at'     => $this->email_verified_at,
            'is_admin'              => $this->isAdmin(),
            'avatar_url'            => $this->avatar_url,
            'address'               => $this->address,
            'city'                  => $this->city,
            'state'                 => $this->state,
            'country'               => $this->country,
            'postal_code'           => $this->postal_code,
            'notify_order_updates'  => $this->notify_order_updates,
            'notify_favourites'     => $this->notify_favourites,
            'created_at'            => $this->created_at,
        ];
    }
}
