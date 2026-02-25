<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('seeder_started');
        Product::create([
            'name' => 'FirstProduct',
            'description' => 'First Seeded Product',
            'unit_price' => 10.00,
            'currency' => 'RON',
            'status' => 'available',
            'product_category_id' => 1,
            'unit_of_measurement' => 'l',
            'user_id' => 1
        ]);

        Product::create([
            'name' => 'SecondProduct',
            'description' => 'Second Seeded Product',
            'unit_price' => 30.00,
            'currency' => 'RON',
            'status' => 'available',
            'product_category_id' => 1,
            'unit_of_measurement' => 'kg',
            'user_id' => 1
        ]);

        Product::create([
            'name' => 'Third',
            'description' => 'Third Seeded Product',
            'unit_price' => 30.00,
            'currency' => 'RON',
            'status' => 'available',
            'product_category_id' => 2,
            'unit_of_measurement' => 'kg',
            'user_id' => 1
        ]);
    }
}
