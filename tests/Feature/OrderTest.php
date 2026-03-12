<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected User $otherBuyer;
    protected User $admin;
    protected ProductCategory $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create([
            'name'  => 'Buyer User',
            'email' => 'buyer@example.com',
        ]);

        $this->otherBuyer = User::factory()->create([
            'name'  => 'Other Buyer',
            'email' => 'otherbuyer@example.com',
        ]);

        $this->seller = User::factory()->create([
            'name'  => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        $this->admin = User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin']);
        $this->admin->assignRole('superadmin');

        $this->category = ProductCategory::create(['name' => 'Test Category']);

        $this->product = Product::factory()->create([
            'user_id'             => $this->seller->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
            'unit_price'          => 50.00,
            'currency'            => 'RON',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createOrderForBuyer(User $buyer, array $orderOverrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'user_id' => $buyer->id,
        ], $orderOverrides));

        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $this->product->id,
            'seller_id'  => $this->seller->id,
        ]);

        return $order;
    }

    // -------------------------------------------------------------------------
    // POST /orders — place order
    // -------------------------------------------------------------------------

    /** @test */
    public function buyer_can_place_an_order()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity'   => 2,
                    ],
                ],
                'currency' => 'RON',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'orders' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                        'total',
                        'items',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id'  => $this->buyer->id,
            'status'   => 'pending',
            'currency' => 'RON',
        ]);
    }

    /** @test */
    public function placing_order_creates_one_order_per_seller()
    {
        $seller2 = User::factory()->create();
        $product2 = Product::factory()->create([
            'user_id'             => $seller2->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
            'unit_price'          => 30.00,
            'currency'            => 'RON',
        ]);

        $response = $this->actingAs($this->buyer)
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                    ['product_id' => $product2->id,      'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201);

        // Should have created 2 separate orders
        $this->assertCount(2, $response->json('orders'));
        $this->assertDatabaseCount('orders', 2);
    }

    /** @test */
    public function cannot_place_order_with_unavailable_product()
    {
        $this->product->update(['status' => 'pending']);

        $response = $this->actingAs($this->buyer)
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Some products are no longer available.']);

        $this->assertDatabaseCount('orders', 0);
    }

    /** @test */
    public function guest_cannot_place_an_order()
    {
        $response = $this->postJson('/api/orders', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function order_validation_requires_items()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function order_calculates_correct_subtotal()
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 3],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id'  => $this->buyer->id,
            'subtotal' => 150.00, // 50.00 * 3
            'total'    => 150.00,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /orders — buyer list
    // -------------------------------------------------------------------------

    /** @test */
    public function buyer_can_list_their_own_orders()
    {
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->otherBuyer); // should not appear

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function buyer_cannot_see_other_buyers_orders()
    {
        $this->createOrderForBuyer($this->otherBuyer);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function buyer_can_filter_orders_by_status()
    {
        $this->createOrderForBuyer($this->buyer, ['status' => 'pending']);
        $this->createOrderForBuyer($this->buyer, ['status' => 'delivered']);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/orders?filter[status]=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    /** @test */
    public function buyer_can_filter_orders_by_seller_id()
    {
        $otherSeller = User::factory()->create();
        $otherProduct = Product::factory()->create([
            'user_id'             => $otherSeller->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);

        // Order from this->seller
        $this->createOrderForBuyer($this->buyer);

        // Order from otherSeller
        $order2 = Order::factory()->create(['user_id' => $this->buyer->id]);
        OrderItem::factory()->create([
            'order_id'   => $order2->id,
            'product_id' => $otherProduct->id,
            'seller_id'  => $otherSeller->id,
        ]);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/orders?filter[seller_id]={$this->seller->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function guest_cannot_list_orders()
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /orders/{id} — buyer show
    // -------------------------------------------------------------------------

    /** @test */
    public function buyer_can_view_their_own_order()
    {
        $order = $this->createOrderForBuyer($this->buyer);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                    'items',
                    'can_cancel',
                ],
            ]);
    }

    /** @test */
    public function buyer_cannot_view_another_buyers_order()
    {
        $order = $this->createOrderForBuyer($this->otherBuyer);

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // GET /seller/orders — seller list
    // -------------------------------------------------------------------------

    /** @test */
    public function seller_can_list_orders_containing_their_products()
    {
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->otherBuyer);

        // Order that belongs to a different seller — should not appear
        $otherSeller = User::factory()->create();
        $otherProduct = Product::factory()->create([
            'user_id'             => $otherSeller->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);
        $otherOrder = Order::factory()->create(['user_id' => $this->buyer->id]);
        OrderItem::factory()->create([
            'order_id'   => $otherOrder->id,
            'product_id' => $otherProduct->id,
            'seller_id'  => $otherSeller->id,
        ]);

        $response = $this->actingAs($this->seller)
            ->getJson('/api/seller/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function seller_can_filter_orders_by_buyer_id()
    {
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->otherBuyer);

        $response = $this->actingAs($this->seller)
            ->getJson("/api/seller/orders?filter[buyer_id]={$this->buyer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function seller_can_filter_orders_by_status()
    {
        $this->createOrderForBuyer($this->buyer, ['status' => 'pending']);
        $this->createOrderForBuyer($this->otherBuyer, ['status' => 'confirmed']);

        $response = $this->actingAs($this->seller)
            ->getJson('/api/seller/orders?filter[status]=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    // -------------------------------------------------------------------------
    // POST /seller/orders/{id}/confirm
    // -------------------------------------------------------------------------

    /** @test */
    public function seller_can_confirm_pending_order()
    {
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'pending']);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/seller/orders/{$order->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function seller_cannot_confirm_already_confirmed_order()
    {
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'confirmed']);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/seller/orders/{$order->id}/confirm");

        $response->assertStatus(403);
    }

    /** @test */
    public function seller_cannot_confirm_order_from_another_seller()
    {
        $otherSeller = User::factory()->create();
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'pending']);

        $response = $this->actingAs($otherSeller)
            ->postJson("/api/seller/orders/{$order->id}/confirm");

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // POST /seller/orders/{id}/ship
    // -------------------------------------------------------------------------

    /** @test */
    public function seller_can_ship_confirmed_order()
    {
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'confirmed']);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/seller/orders/{$order->id}/ship");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');
    }

    /** @test */
    public function seller_cannot_ship_pending_order()
    {
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'pending']);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/seller/orders/{$order->id}/ship");

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // POST /seller/orders/{id}/deliver
    // -------------------------------------------------------------------------

    /** @test */
    public function seller_can_deliver_shipped_order()
    {
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'shipped']);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/seller/orders/{$order->id}/deliver");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'delivered',
        ]);

        $this->assertNotNull(
            Order::find($order->id)->completed_at
        );
    }

    /** @test */
    public function seller_cannot_deliver_unshipped_order()
    {
        $order = $this->createOrderForBuyer($this->buyer, ['status' => 'confirmed']);

        $response = $this->actingAs($this->seller)
            ->postJson("/api/seller/orders/{$order->id}/deliver");

        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // GET /admin/orders — admin list
    // -------------------------------------------------------------------------

    /** @test */
    public function admin_can_list_all_orders()
    {
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->otherBuyer);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_filter_orders_by_status()
    {
        $this->createOrderForBuyer($this->buyer, ['status' => 'pending']);
        $this->createOrderForBuyer($this->otherBuyer, ['status' => 'delivered']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/orders?filter[status]=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    /** @test */
    public function admin_can_filter_orders_by_buyer_id()
    {
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->otherBuyer);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/orders?filter[buyer_id]={$this->buyer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_filter_orders_by_seller_id()
    {
        $this->createOrderForBuyer($this->buyer);
        $this->createOrderForBuyer($this->otherBuyer);

        $otherSeller = User::factory()->create();
        $otherProduct = Product::factory()->create([
            'user_id'             => $otherSeller->id,
            'product_category_id' => $this->category->id,
            'status'              => 'available',
        ]);
        $order = Order::factory()->create(['user_id' => $this->buyer->id]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $otherProduct->id,
            'seller_id'  => $otherSeller->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/orders?filter[seller_id]={$this->seller->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function admin_can_view_single_order()
    {
        $order = $this->createOrderForBuyer($this->buyer);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                    'buyer',
                    'items',
                ],
            ]);
    }

    /** @test */
    public function non_admin_cannot_access_admin_orders()
    {
        $response = $this->actingAs($this->buyer)
            ->getJson('/api/admin/orders');

        $response->assertStatus(403);
    }

    /** @test */
    public function orders_are_sorted_by_newest_first_by_default()
    {
        $older = $this->createOrderForBuyer($this->buyer);
        \DB::table('orders')->where('id', $older->id)->update(['created_at' => now()->subDays(2)]);

        $newer = $this->createOrderForBuyer($this->buyer);
        \DB::table('orders')->where('id', $newer->id)->update(['created_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    /** @test */
    public function orders_response_has_correct_pagination_structure()
    {
        Order::factory()->count(20)->create(['user_id' => $this->buyer->id]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }
}