<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'product_name'        => $this->product_name,
            'product_description' => $this->product_description,
            'unit_price'          => [
                'amount'    => (float) $this->unit_price,
                'currency'  => $this->currency,
                'formatted' => number_format((float) $this->unit_price, 2) . ' ' . $this->currency,
            ],
            'quantity'   => $this->quantity,
            'subtotal'   => [
                'amount'    => (float) $this->subtotal,
                'currency'  => $this->currency,
                'formatted' => number_format((float) $this->subtotal, 2) . ' ' . $this->currency,
            ],
            'status' => $this->status,
            'seller' => [
                'id'   => $this->seller_id,
                'name' => $this->seller?->name,
            ],
            'product' => $this->whenLoaded('product', fn() => [
                'id'     => $this->product->id,
                'status' => $this->product->status,
                'images' => collect($this->product->getMedia('images'))->map(fn($media) => [
                    'thumb'   => $media->getUrl('thumb'),
                    'preview' => $media->getUrl('preview'),
                ]),
            ]),
        ];
    }
}
