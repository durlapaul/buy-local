<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'unit_of_measurement' => $this->unit_of_measurement,
            'price' => [
                'amount' => (float) $this->unit_price,
                'currency' => $this->currency,
                'formatted' => number_format($this->unit_price, 2) . ' ' . $this->currency,
            ],
            
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            
            'seller' => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                'location' => [
                    'city' => $this->seller->city,
                    'state' => $this->seller->state,
                    'country' => $this->seller->country,
                ],
            ],
            
            'relevance' => $this->when(isset($this->relevance), round($this->relevance, 2)),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
