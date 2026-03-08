<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10, 500);

        return [
            'user_id'      => User::factory(),
            'status'       => 'pending',
            'subtotal'     => $subtotal,
            'tax'          => 0,
            'shipping'     => 0,
            'total'        => $subtotal,
            'currency'     => 'RON',
            'notes'        => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    public function shipped(): static
    {
        return $this->state(['status' => 'shipped']);
    }

    public function delivered(): static
    {
        return $this->state([
            'status'       => 'delivered',
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
