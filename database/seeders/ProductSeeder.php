<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productsData = [
            [
                'name' => 'Organic Apples',
                'description' => 'Fresh, locally grown organic apples. Sweet and crispy!',
                'status' => 'available',
                'unit_of_measurement' => 'kg',
                'unit_price' => 12.50,
                'currency' => 'RON',
                'images_count' => 3,
            ],
            [
                'name' => 'Farm Fresh Eggs',
                'description' => 'Free-range chicken eggs from our local farm.',
                'status' => 'available',
                'unit_of_measurement' => 'dozen',
                'unit_price' => 18.00,
                'currency' => 'RON',
                'images_count' => 2,
            ],
            [
                'name' => 'Homemade Bread',
                'description' => 'Freshly baked sourdough bread, made daily.',
                'status' => 'available',
                'unit_of_measurement' => 'loaf',
                'unit_price' => 8.50,
                'currency' => 'RON',
                'images_count' => 4,
            ],
            [
                'name' => 'Organic Tomatoes',
                'description' => 'Vine-ripened organic tomatoes, perfect for salads.',
                'status' => 'available',
                'unit_of_measurement' => 'kg',
                'unit_price' => 15.00,
                'currency' => 'RON',
                'images_count' => 2,
            ],
            [
                'name' => 'Fresh Honey',
                'description' => 'Pure, raw honey from local beekeepers.',
                'status' => 'available',
                'unit_of_measurement' => 'jar (500g)',
                'unit_price' => 35.00,
                'currency' => 'RON',
                'images_count' => 3,
            ],
            [
                'name' => 'Handwoven Basket',
                'description' => 'Beautiful handwoven basket, perfect for storage or decor.',
                'status' => 'available',
                'unit_of_measurement' => 'piece',
                'unit_price' => 45.00,
                'currency' => 'RON',
                'images_count' => 2,
            ],
            [
                'name' => 'Organic Carrots',
                'description' => 'Sweet, crunchy organic carrots.',
                'status' => 'available',
                'unit_of_measurement' => 'kg',
                'unit_price' => 10.00,
                'currency' => 'RON',
                'images_count' => 2,
            ],
            [
                'name' => 'Goat Cheese',
                'description' => 'Artisan goat cheese, creamy and delicious.',
                'status' => 'available',
                'unit_of_measurement' => 'piece (200g)',
                'unit_price' => 25.00,
                'currency' => 'RON',
                'images_count' => 3,
            ],
        ];

        $categories = ProductCategory::all();

        foreach ($productsData as $productData) {
            $imagesCount = $productData['images_count'];
            unset($productData['images_count']);

            // Random user and category
            $productData['user_id'] = 1;
            $productData['product_category_id'] = $categories->random()->id;

            // Create product
            $product = Product::create($productData);

            for ($i = 0; $i < $imagesCount; $i++) {
                $this->addPlaceholderImage($product, $i);
            }

            $this->command->info("Created: {$product->name} with {$imagesCount} images");
        }

        $this->command->info('Products seeded successfully!');
    }

    private function addPlaceholderImage(Product $product, int $index): void
    {
        // Create a simple colored rectangle as placeholder
        $width = 800;
        $height = 600;
        
        // Random color
        $colors = [
            [76, 175, 80],   // Green
            [33, 150, 243],  // Blue
            [255, 152, 0],   // Orange
            [233, 30, 99],   // Pink
            [156, 39, 176],  // Purple
        ];
        
        $color = $colors[array_rand($colors)];
        
        // Create image
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // Add text
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = $product->name;
        imagestring($image, 5, 50, 50, $text, $white);
        imagestring($image, 3, 50, 80, "Image " . ($index + 1), $white);
        
        // Save to temporary file
        $tempPath = storage_path("app/temp_product_{$product->id}_{$index}.jpg");
        imagejpeg($image, $tempPath, 90);
        imagedestroy($image);
        
        // Add to media library
        $product->addMedia($tempPath)
            ->withCustomProperties(['order' => $index])
            ->toMediaCollection('images');
        
        // Clean up temp file
        @unlink($tempPath);
    }


}
