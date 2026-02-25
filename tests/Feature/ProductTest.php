<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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

}
