<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = $this->faker->randomFloat(2, 5, 200);
        $quantity  = $this->faker->numberBetween(1, 10);

        return [
            'order_id'            => Order::factory(),
            'product_id'          => Product::factory(),
            'seller_id'           => User::factory(),
            'product_name'        => $this->faker->words(3, true),
            'product_description' => $this->faker->sentence(),
            'unit_price'          => $unitPrice,
            'quantity'            => $quantity,
            'subtotal'            => $unitPrice * $quantity,
            'currency'            => 'RON',
            'status'              => 'pending',
        ];
    }
}
