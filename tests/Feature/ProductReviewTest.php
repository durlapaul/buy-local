<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected User $otherUser;
    protected ProductCategory $category;
    protected Product $product;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create([
            'name'  => 'Buyer User',
            'email' => 'buyer@example.com',
        ]);

        $this->seller = User::factory()->create([
            'name'  => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        $this->otherUser = User::factory()->create([
            'name'  => 'Other User',
            'email' => 'other@example.com',
        ]);

        $this->category = ProductCategory::create(['name' => 'Test Category']);

        $this->product = Product::factory()->create([
            'user_id'             => $this->seller->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        $this->order = Order::factory()->create([
            'user_id' => $this->buyer->id,
            'status'   => 'delivered',
            'completed_at' => now()->subDay(),
        ]);

        OrderItem::factory()->create([
            'order_id'   => $this->order->id,
            'product_id' => $this->product->id,
            'seller_id'  => $this->seller->id,
        ]);
    }

    /** @test */
    public function guest_cannot_leave_a_review()
    {
        $response = $this->postJson("/api/orders/{$this->order->id}/reviews", [
            'product_id' => $this->product->id,
            'rating'     => 5,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function buyer_can_leave_a_review_for_a_delivered_order()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 4,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Review submitted successfully.',
                'rating'  => 4,
            ]);

        $this->assertDatabaseHas('product_reviews', [
            'reviewer_id' => $this->buyer->id,
            'product_id'  => $this->product->id,
            'order_id'    => $this->order->id,
            'rating'      => 4,
        ]);
    }

    /** @test */
    public function buyer_can_leave_a_review_for_a_confirmed_order()
    {
        $this->order->update(['status' => 'confirmed']);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function buyer_can_leave_a_review_for_a_shipped_order()
    {
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 3,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function buyer_cannot_review_a_pending_order()
    {
        $this->order->update(['status' => 'pending']);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function buyer_cannot_review_a_cancelled_order()
    {
        $this->order->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function non_buyer_cannot_review_an_order()
    {
        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function seller_cannot_review_their_own_product_order()
    {
        $response = $this->actingAs($this->seller)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_review_a_product_not_in_the_order()
    {
        $otherProduct = Product::factory()->create([
            'user_id'             => $this->seller->id,
            'product_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $otherProduct->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function cannot_review_after_5_day_window_has_closed()
    {
        $this->order->update([
            'status'       => 'delivered',
            'completed_at' => now()->subDays(6),
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function can_review_within_the_5_day_window()
    {
        $this->order->update([
            'status'       => 'delivered',
            'completed_at' => now()->subDays(4),
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function buyer_can_update_their_existing_review()
    {
        ProductReview::create([
            'reviewer_id'      => $this->buyer->id,
            'product_id'       => $this->product->id,
            'order_id'         => $this->order->id,
            'rating'           => 3,
            'is_auto_generated' => false,
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 5,
            ]);

        $response->assertStatus(201)
            ->assertJson(['rating' => 5]);

        $this->assertDatabaseHas('product_reviews', [
            'reviewer_id' => $this->buyer->id,
            'product_id'  => $this->product->id,
            'order_id'    => $this->order->id,
            'rating'      => 5,
        ]);

        // Only one review should exist, not two
        $this->assertSame(1, ProductReview::where([
            'reviewer_id' => $this->buyer->id,
            'product_id'  => $this->product->id,
            'order_id'    => $this->order->id,
        ])->count());
    }

    /** @test */
    public function review_requires_a_rating()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function review_requires_a_product_id()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'rating' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    /** @test */
    public function rating_must_be_between_1_and_5()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 6,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function submitting_review_updates_product_avg_rating()
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 4,
            ]);

        $this->assertEquals(4.00, $this->product->fresh()->avg_rating);
        $this->assertEquals(1, $this->product->fresh()->review_count);
    }

    /** @test */
    public function updating_review_recalculates_product_avg_rating()
    {
        ProductReview::create([
            'reviewer_id'      => $this->buyer->id,
            'product_id'       => $this->product->id,
            'order_id'         => $this->order->id,
            'rating'           => 2,
            'is_auto_generated' => false,
        ]);

        $this->actingAs($this->buyer)
            ->postJson("/api/orders/{$this->order->id}/reviews", [
                'product_id' => $this->product->id,
                'rating'     => 4,
            ]);

        $this->assertEquals(4.00, $this->product->fresh()->avg_rating);
        $this->assertEquals(1, $this->product->fresh()->review_count);
    }
}