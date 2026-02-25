<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_category_id' => ProductCategory::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['available', 'unavailable', 'draft']),
            'unit_of_measurement' => fake()->randomElement(['kg', 'l', 'piece', 'box']),
            'unit_price' => fake()->randomFloat(2, 5, 100),
            'currency' => 'RON',
        ];
    }
}