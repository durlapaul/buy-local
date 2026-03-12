<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavouriteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
            'city'  => 'Cluj-Napoca',
        ]);

        $this->otherUser = User::factory()->create([
            'name'  => 'Other User',
            'email' => 'other@example.com',
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Test Category',
        ]);
    }

    /** @test */
    public function guest_cannot_view_favourites()
    {
        $response = $this->getJson('/api/favourites');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_view_their_favourites()
    {
        $products = Product::factory()->count(3)->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        $this->user->favouriteProducts()->attach($products->pluck('id'));

        $response = $this->actingAs($this->user)->getJson('/api/favourites');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'seller', 'category'],
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function favourites_only_returns_available_products()
    {
        $available = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        $pending = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'pending',
        ]);

        $this->user->favouriteProducts()->attach([$available->id, $pending->id]);

        $response = $this->actingAs($this->user)->getJson('/api/favourites');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $available->id);
    }

    /** @test */
    public function user_only_sees_their_own_favourites()
    {
        $product = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        // Only otherUser favourites this product
        $this->otherUser->favouriteProducts()->attach($product->id);

        $response = $this->actingAs($this->user)->getJson('/api/favourites');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function favourites_can_be_paginated()
    {
        $products = Product::factory()->count(20)->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        $this->user->favouriteProducts()->attach($products->pluck('id'));

        $response = $this->actingAs($this->user)->getJson('/api/favourites?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    /** @test */
    public function favourites_can_be_filtered_by_category()
    {
        $category2 = ProductCategory::create(['name' => 'Category 2']);

        $productsInCategory1 = Product::factory()->count(2)->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        $productInCategory2 = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $category2->id,
            'status'              => 'available',
        ]);

        $this->user->favouriteProducts()->attach(
            $productsInCategory1->pluck('id')->push($productInCategory2->id)
        );

        $response = $this->actingAs($this->user)
            ->getJson("/api/favourites?filter[category_id]={$this->category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function favourites_can_be_sorted_by_price()
    {
        $cheap = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
            'unit_price'          => 10,
        ]);

        $expensive = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
            'unit_price'          => 50,
        ]);

        $this->user->favouriteProducts()->attach([$cheap->id, $expensive->id]);

        $response = $this->actingAs($this->user)->getJson('/api/favourites?sort=unit_price');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $cheap->id)
            ->assertJsonPath('data.1.id', $expensive->id);
    }

    /** @test */
    public function guest_cannot_toggle_favourite()
    {
        $product = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
        ]);

        $response = $this->postJson("/api/products/{$product->id}/favourite");

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_add_product_to_favourites()
    {
        $product = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/favourite");

        $response->assertStatus(200)
            ->assertJson([
                'message'      => 'Added to favourites',
                'is_favourited' => true,
            ]);

        $this->assertDatabaseHas('user_favourite_products', [
            'user_id'    => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    /** @test */
    public function user_can_remove_product_from_favourites()
    {
        $product = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
        ]);

        $this->user->favouriteProducts()->attach($product->id);

        $response = $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/favourite");

        $response->assertStatus(200)
            ->assertJson([
                'message'      => 'Removed from favourites',
                'is_favourited' => false,
            ]);

        $this->assertDatabaseMissing('user_favourite_products', [
            'user_id'    => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    /** @test */
    public function toggling_favourite_twice_removes_it()
    {
        $product = Product::factory()->create([
            'user_id'             => $this->otherUser->id,
            'product_category_id' => $this->category->id,
        ]);

        $this->actingAs($this->user)->postJson("/api/products/{$product->id}/favourite");
        $this->actingAs($this->user)->postJson("/api/products/{$product->id}/favourite");

        $this->assertDatabaseMissing('user_favourite_products', [
            'user_id'    => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    /** @test */
    public function toggling_non_existent_product_returns_404()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products/99999/favourite');

        $response->assertStatus(404);
    }
}