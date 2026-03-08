<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class OrderSeeder extends Seeder
{
    private const STATUSES = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    private const CURRENCY = 'RON';

    public function run(): void
    {
        $superadmin = User::find(1);
        $seller     = User::find(2);

        $sellerProducts     = Product::where('user_id', 2)->where('status', 'available')->get();
        $superadminProducts = Product::where('user_id', 1)->where('status', 'available')->get();

        if ($sellerProducts->isEmpty() || $superadminProducts->isEmpty()) {
            $this->command->warn('Not enough products. Run ProductSeeder first.');
            return;
        }

        // ── Superadmin BUYS from seller (60 orders) ──────────────────────
        $this->command->info('Seeding superadmin buy orders...');
        for ($i = 0; $i < 60; $i++) {
            $this->createOrder(
                buyer: $superadmin,
                products: $sellerProducts->random(rand(1, min(3, $sellerProducts->count()))),
                status: self::STATUSES[$i % count(self::STATUSES)],
                notes: $i % 5 === 0 ? 'Please deliver in the morning.' : null,
            );
        }

        // ── Seller BUYS from superadmin (60 orders) ──────────────────────
        $this->command->info('Seeding seller buy orders...');
        for ($i = 0; $i < 60; $i++) {
            $this->createOrder(
                buyer: $seller,
                products: $superadminProducts->random(rand(1, min(3, $superadminProducts->count()))),
                status: self::STATUSES[$i % count(self::STATUSES)],
                notes: $i % 7 === 0 ? 'Fragile, handle with care.' : null,
            );
        }

        $total = Order::count();
        $this->command->info("Done! {$total} orders seeded.");
    }

    private function createOrder(
        User $buyer,
        Collection $products,
        string $status,
        ?string $notes = null,
    ): void {
        $items    = [];
        $subtotal = 0;

        foreach ($products as $product) {
            $quantity  = rand(1, 5);
            $unitPrice = $product->unit_price;
            $itemTotal = round($unitPrice * $quantity, 2);
            $subtotal += $itemTotal;

            $items[] = [
                'product_id'          => $product->id,
                'seller_id'           => $product->user_id,
                'product_name'        => $product->name,
                'product_description' => $product->description,
                'unit_price'          => $unitPrice,
                'quantity'            => $quantity,
                'subtotal'            => $itemTotal,
                'currency'            => self::CURRENCY,
                'status'              => $status,
            ];
        }

        $subtotal = round($subtotal, 2);
        $tax      = round($subtotal * 0.19, 2);
        $shipping = 15.00;
        $total    = round($subtotal + $tax + $shipping, 2);

        $daysAgo = rand(1, 60);

        \DB::transaction(function () use (
            $buyer, $status, $subtotal, $tax, $shipping,
            $total, $notes, $items, $daysAgo
        ) {
            $order = Order::create([
                'user_id'      => $buyer->id,
                'status'       => $status,
                'currency'     => self::CURRENCY,
                'subtotal'     => $subtotal,
                'tax'          => $tax,
                'shipping'     => $shipping,
                'total'        => $total,
                'notes'        => $notes,
                'completed_at' => in_array($status, ['delivered', 'cancelled'])
                    ? now()->subDays($daysAgo)
                    : null,
                'created_at'   => now()->subDays($daysAgo),
                'updated_at'   => now()->subDays(rand(0, $daysAgo)),
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }
        });
    }
}