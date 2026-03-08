<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCancelled;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;


class OrderController extends Controller
{
    public function indexForBuyer(Request $request): AnonymousResourceCollection
    {
        $entities = QueryBuilder::for(
            Order::where('user_id', $request->user()->id)
                ->with(['items.seller', 'items.product'])
                ->withCount('items')
            )
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('seller_id', function ($query, $value) {
                    $query->whereHas('items', fn($q) => $q->where('seller_id', $value));
                }),
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
            ])
            ->defaultSort('-created_at')
            ->paginate(15);

        return OrderResource::collection($entities);
    }

    public function indexForSeller(Request $request): AnonymousResourceCollection
    {
        $sellerId = $request->user()->id;

        $entities = QueryBuilder::for(
            Order::whereHas('items', fn($q) => $q->where('seller_id', $sellerId))
                ->with([
                    'items' => fn($q) => $q
                        ->where('seller_id', $sellerId)
                        ->with(['product', 'seller']),
                    'buyer',
                ])
                ->withCount('items')
            )
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('buyer_id', 'user_id'),
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
            ])
            ->defaultSort('-created_at')
            ->paginate(15);

        return OrderResource::collection($entities);
    }

    public function indexForAdmin(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('canModerate', Order::class);

        $entities = QueryBuilder::for(
            Order::with(['items.seller', 'items.product', 'buyer'])
                ->withCount('items')
            )
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('buyer_id', 'user_id'),
                AllowedFilter::callback('seller_id', function ($query, $value) {
                    $query->whereHas('items', fn($q) => $q->where('seller_id', $value));
                }),
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
            ])
            ->defaultSort('-created_at')
            ->paginate(15);

        return OrderResource::collection($entities);
    }
   

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Order::class);

        $validated = $request->validate([
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'currency'           => ['nullable', 'string', 'size:3'],
        ]);

        $currency = $validated['currency'] ?? 'RON';

        $productIds = collect($validated['items'])->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->where('status', 'available')
            ->get()
            ->keyBy('id');

        $unavailable = $productIds->diff($products->keys());
        if ($unavailable->isNotEmpty()) {
            return response()->json([
                'message'         => 'Some products are no longer available.',
                'unavailable_ids' => $unavailable->values(),
            ], 422);
        }

        $itemsBySeller = collect($validated['items'])->groupBy(
            fn($item) => $products[$item['product_id']]->user_id
        );

        $entities = [];

        DB::transaction(function () use (
            $request,
            $itemsBySeller,
            $products,
            $currency,
            $validated,
            &$entities
        ) {
            foreach ($itemsBySeller as $sellerId => $sellerItems) {
                $subtotal = $sellerItems->sum(
                    fn($item) => $products[$item['product_id']]->unit_price * $item['quantity']
                );

                $entity = Order::create([
                    'user_id'  => $request->user()->id,
                    'status'   => 'pending',
                    'subtotal' => $subtotal,
                    'tax'      => 0,
                    'shipping' => 0,
                    'total'    => $subtotal,
                    'currency' => $currency,
                    'notes'    => $validated['notes'] ?? null,
                ]);

                foreach ($sellerItems as $item) {
                    $product = $products[$item['product_id']];

                    $entity->items()->create([
                        'product_id'          => $product->id,
                        'seller_id'           => $sellerId,
                        'product_name'        => $product->name,
                        'product_description' => $product->description,
                        'unit_price'          => $product->unit_price,
                        'quantity'            => $item['quantity'],
                        'currency'            => $currency,
                        'status'              => 'pending',
                    ]);
                }

                $entity->load(['items.seller', 'items.product']);
                $entities[] = $entity;

                event(new OrderPlaced($entity, $sellerId));
            }
        });

        return response()->json([
            'message' => 'Orders placed successfully.',
            'orders'  => OrderResource::collection(collect($entities)),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Order $entity): OrderResource
    {
        Gate::authorize('view', $entity);

        $entity->load(['items.seller', 'items.product', 'buyer']);

        return new OrderResource($entity);
    }

    public function reject(Request $request, Order $entity): OrderResource
    {
        Gate::authorize('reject', $entity);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500']
        ]);

        $entity->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);

        event(new OrderStatusUpdated($entity, 'rejected', $entity->rejection_reason));

        $entity->load(['items.seller', 'items.product', 'buyer']);

        return new OrderResource($entity);
    }

    public function cancel(Request $request, Order $entity): OrderResource
    {
        Gate::authorize('cancel', $entity);

        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        $entity->update([
            'status'        => 'cancelled',
            'cancel_reason' => $request->cancel_reason,
        ]);

        $entity->update(['status' => 'cancelled']);

        event(new OrderCancelled($entity));

        $entity->load(['items.seller', 'items.product']);

        return new OrderResource($entity);
    }

    public function confirm(Request $request, Order $entity): OrderResource
    {
        Gate::authorize('confirm', $entity);

        $entity->update(['status' => 'confirmed']);

        event(new OrderStatusUpdated($entity, 'confirmed'));

        $entity->load(['items.seller', 'items.product', 'buyer']);

        return new OrderResource($entity);
    }


    /**
     * Update the specified resource in storage.
     */
    public function ship(Request $request, Order $entity): OrderResource
    {
        Gate::authorize('ship', $entity);

        $entity->update(['status' => 'shipped']);

        event(new OrderStatusUpdated($entity, 'shipped'));

        $entity->load(['items.seller', 'items.product', 'buyer']);

        return new OrderResource($entity);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deliver(Request $request, Order $entity): OrderResource
    {
        Gate::authorize('deliver', $entity);

        $entity->update([
            'status'       => 'delivered',
            'completed_at' => now(),
        ]);

        event(new OrderStatusUpdated($entity, 'delivered'));

        $entity->load(['items.seller', 'items.product', 'buyer']);

        return new OrderResource($entity);
    }
}
