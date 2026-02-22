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
    }
}
