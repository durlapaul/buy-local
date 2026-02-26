<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'city' => 'Cluj-Napoca',
            'country' => 'Romania',
        ]);

        $this->otherUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'other@example.com',
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Test Category',
        ]);
    }

    /** @test */
    public function guests_can_view_products_list()
    {
        // Arrange
        Product::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Act
        $response = $this->getJson('/api/products');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'unit_of_measurement',
                        'price',
                        'category',
                        'seller',
                    ]
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function guests_can_view_single_product()
    {
        // Arrange
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
            'name' => 'Test Product',
        ]);

        // Act
        $response = $this->getJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => 'Test Product',
                ]
            ]);
    }

    /** @test */
    public function can_filter_products_by_category()
    {
        // Arrange
        $category2 = ProductCategory::create(['name' => 'Category 2']);

        Product::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $category2->id,
        ]);

        // Act
        $response = $this->getJson("/api/products?filter[category_id]={$this->category->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function can_filter_products_by_status()
    {
        // Arrange
        Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
            'status' => 'available',
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
            'status' => 'draft',
        ]);

        // Act
        $response = $this->getJson('/api/products?filter[status]=available');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function can_sort_products_by_price()
    {
        // Arrange
        Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
            'unit_price' => 50,
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
            'unit_price' => 20,
        ]);

        // Act - Sort ascending
        $response = $this->getJson('/api/products?sort=unit_price');

        // Assert
        $response->assertStatus(200);
        $this->assertEquals(20, $response->json('data.0.price.amount'));
        $this->assertEquals(50, $response->json('data.1.price.amount'));

        // Act - Sort descending
        $response = $this->getJson('/api/products?sort=-unit_price');

        // Assert
        $this->assertEquals(50, $response->json('data.0.price.amount'));
        $this->assertEquals(20, $response->json('data.1.price.amount'));
    }

    /** @test */
    public function authenticated_user_can_create_product()
    {
        // Arrange
        $productData = [
            'name' => 'New Product',
            'description' => 'Product description',
            'status' => 'available',
            'unit_of_measurement' => 'kg',
            'unit_price' => 15.50,
            'currency' => 'RON',
            'product_category_id' => $this->category->id,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', $productData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'New Product',
                    'status' => 'available'
                ],
                'message' => __('messages.products.created'),
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function guest_cannot_create_product()
    {
        // Arrange
        $productData = [
            'name' => 'New Product',
            'status' => 'available',
            'unit_of_measurement' => 'kg',
            'unit_price' => 15.50,
            'currency' => 'RON',
            'product_category_id' => $this->category->id,
        ];

        // Act
        $response = $this->postJson('/api/products', $productData);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function product_creation_requires_valid_data()
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'status',
                'unit_of_measurement',
                'unit_price',
                'currency',
                'product_category_id',
            ]);
    }

    /** @test */
    public function owner_can_update_their_product()
    {
        // Arrange
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
            'name' => 'Original Name',
            'unit_price' => 10,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Name',
                'unit_price' => 20,
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'unit_price' => 20,
        ]);
    }

    /** @test */
    public function non_owner_cannot_update_product()
    {
        // Arrange
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Act
        $response = $this->actingAs($this->otherUser)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Hacked Name',
            ]);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'name' => 'Hacked Name',
        ]);
    }

    /** @test */
    public function owner_can_delete_their_product()
    {
        // Arrange
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    /** @test */
    public function non_owner_cannot_delete_product()
    {
        // Arrange
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Act
        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function can_paginate_products()
    {
        // Arrange
        Product::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Act
        $response = $this->getJson('/api/products?per_page=10');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_can_add_single_image_to_product(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post("/api/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->image('product.jpg'),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'image' => ['id', 'url', 'thumb', 'preview', 'order']
            ]);

        $this->assertEquals(1, $product->fresh()->getMedia('images')->count());
    }

    public function test_can_add_multiple_images_sequentially(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Add 3 images one by one
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->actingAs($this->user)
                ->post("/api/products/{$product->id}/images", [
                    'image' => UploadedFile::fake()->image("image{$i}.jpg"),
                ]);

            $response->assertStatus(201);
        }

        $this->assertEquals(3, $product->fresh()->getMedia('images')->count());
    }

    public function test_cannot_add_more_than_10_images(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Add 10 images
        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($this->user)
                ->post("/api/products/{$product->id}/images", [
                    'image' => UploadedFile::fake()->image("image{$i}.jpg"),
                ]);
        }

        // Try to add 11th image
        $response = $this->actingAs($this->user)
            ->post("/api/products/{$product->id}/images", [
                'image' => UploadedFile::fake()->image('image11.jpg'),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Maximum 10 images allowed per product'
            ]);
    }

    /** @test */
    public function test_can_reorder_product_images(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        // Add 3 images
        $image1 = $product->addMedia(UploadedFile::fake()->image('image1.jpg'))
            ->toMediaCollection('images');
        $image2 = $product->addMedia(UploadedFile::fake()->image('image2.jpg'))
            ->toMediaCollection('images');
        $image3 = $product->addMedia(UploadedFile::fake()->image('image3.jpg'))
            ->toMediaCollection('images');

        // Initial order should be 1, 2, 3
        $this->assertEquals(1, $image1->fresh()->order_column);
        $this->assertEquals(2, $image2->fresh()->order_column);
        $this->assertEquals(3, $image3->fresh()->order_column);

        // Reorder: 3, 1, 2
        $response = $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/images/reorder", [
                'image_ids' => [$image3->id, $image1->id, $image2->id],
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Images reordered successfully']);

        // Verify new order
        $this->assertEquals(1, $image3->fresh()->order_column);
        $this->assertEquals(2, $image1->fresh()->order_column);
        $this->assertEquals(3, $image2->fresh()->order_column);

        // Verify order in collection
        $orderedMedia = $product->fresh()->getMedia('images');
        
        $this->assertEquals($image3->id, $orderedMedia[0]->id);
        $this->assertEquals($image1->id, $orderedMedia[1]->id);
        $this->assertEquals($image2->id, $orderedMedia[2]->id);
    }

    /** @test */
    public function test_cannot_reorder_images_with_invalid_ids(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $image1 = $product->addMedia(UploadedFile::fake()->image('image1.jpg'))
            ->toMediaCollection('images');

        // Try to reorder with non-existent ID
        $response = $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/images/reorder", [
                'image_ids' => [$image1->id, 99999],
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_reorder_another_products_images(): void
    {
        Storage::fake('public');

        $product1 = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $product2 = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $image1 = $product1->addMedia(UploadedFile::fake()->image('image1.jpg'))
            ->toMediaCollection('images');
        $image2 = $product2->addMedia(UploadedFile::fake()->image('image2.jpg'))
            ->toMediaCollection('images');

        // Try to reorder product1 with product2's image
        $response = $this->actingAs($this->user)
            ->postJson("/api/products/{$product1->id}/images/reorder", [
                'image_ids' => [$image1->id, $image2->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => "Image {$image2->id} does not belong to this product"]);
    }

    /** @test */
    public function test_non_owner_cannot_reorder_images(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $image1 = $product->addMedia(UploadedFile::fake()->image('image1.jpg'))
            ->toMediaCollection('images');
        $image2 = $product->addMedia(UploadedFile::fake()->image('image2.jpg'))
            ->toMediaCollection('images');

        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/products/{$product->id}/images/reorder", [
                'image_ids' => [$image2->id, $image1->id],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_product_primary_image_updates_after_reorder(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $image1 = $product->addMedia(UploadedFile::fake()->image('image1.jpg'))
            ->toMediaCollection('images');
        $image2 = $product->addMedia(UploadedFile::fake()->image('image2.jpg'))
            ->toMediaCollection('images');

        // Primary image should be image1
        $this->assertEquals($image1->getUrl(), $product->fresh()->primary_image_url);

        // Reorder: put image2 first
        $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/images/reorder", [
                'image_ids' => [$image2->id, $image1->id],
            ]);

        // Primary image should now be image2
        $this->assertEquals($image2->getUrl(), $product->fresh()->primary_image_url);
    }

    /** @test */
    public function test_reorder_persists_after_product_reload(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'product_category_id' => $this->category->id,
        ]);

        $image1 = $product->addMedia(UploadedFile::fake()->image('image1.jpg'))
            ->toMediaCollection('images');
        $image2 = $product->addMedia(UploadedFile::fake()->image('image2.jpg'))
            ->toMediaCollection('images');
        $image3 = $product->addMedia(UploadedFile::fake()->image('image3.jpg'))
            ->toMediaCollection('images');

        // Reorder
        $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/images/reorder", [
                'image_ids' => [$image3->id, $image1->id, $image2->id],
            ]);

        // Fresh load from database
        $freshProduct = Product::find($product->id);
        $orderedMedia = $freshProduct->getMedia('images');

        // Verify order persisted
        $this->assertEquals($image3->id, $orderedMedia[0]->id);
        $this->assertEquals($image1->id, $orderedMedia[1]->id);
        $this->assertEquals($image2->id, $orderedMedia[2]->id);
    }

}
