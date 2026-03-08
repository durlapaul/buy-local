<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_number' => $this->order_number,
            'status'       => $this->status,
            'currency'     => $this->currency,
            'subtotal'     => [
                'amount'    => (float) $this->subtotal,
                'currency'  => $this->currency,
                'formatted' => number_format((float) $this->subtotal, 2) . ' ' . $this->currency,
            ],
            'tax' => [
                'amount'    => (float) $this->tax,
                'currency'  => $this->currency,
                'formatted' => number_format((float) $this->tax, 2) . ' ' . $this->currency,
            ],
            'shipping' => [
                'amount'    => (float) $this->shipping,
                'currency'  => $this->currency,
                'formatted' => number_format((float) $this->shipping, 2) . ' ' . $this->currency,
            ],
            'total' => [
                'amount'    => (float) $this->total,
                'currency'  => $this->currency,
                'formatted' => number_format((float) $this->total, 2) . ' ' . $this->currency,
            ],
            'notes'        => $this->notes,
            'cancel_reason' => $this->cancel_reason ?? '',
            'rejection_reason' => $this->rejection_reason ?? '',
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at'   => $this->created_at->toISOString(),
            'updated_at'   => $this->updated_at->toISOString(),

            'buyer' => $this->when(
                $request->user()?->isAdmin(),
                fn() => [
                    'id'    => $this->buyer->id,
                    'name'  => $this->buyer->name,
                    'email' => $this->buyer->email,
                ]
            ),

            'seller' => $this->when(
                $this->relationLoaded('items') && $this->items->isNotEmpty(),
                fn() => [
                    'id'   => $this->items->first()->seller_id,
                    'name' => $this->items->first()->seller?->name,
                ]
            ),

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'items_count' => $this->whenCounted('items'),

            'can_cancel' => $this->status === 'pending',
            'is_active'  => in_array($this->status, ['pending', 'confirmed', 'shipped']),
        ];
    }
}
