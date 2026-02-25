<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Filters\ProductLocationFilter;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $entities = QueryBuilder::for(Product::class)
            ->allowedFilters([
                AllowedFilter::scope('search'),

                AllowedFilter::scope('location'),

                AllowedFilter::exact('category_id', 'product_category_id'),
                AllowedFilter::exact('status'),
            ])->allowedSorts([
                'name',
                'unit_price',
                'created_at',
                'updated_at'
            ])->defaultSort('-created_at')
            ->with(['seller', 'category'])
            ->paginate(request()->input('per_page', 15))
            ->withQueryString();

        
        return ProductResource::collection($entities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:available,unavailable,draft',
            'unit_of_measurement' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'product_category_id' => 'required|exists:product_categories,id',
        ]);

        $validated['user_id'] = $request->user()->id;

        $entity = Product::create($validated);

        $entity->load(['seller', 'category']);

        return (new ProductResource($entity))
            ->additional(['message' => __('messages.products.created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $entity)
    {
        $entity->load(['seller', 'category']);

        return new ProductResource($entity);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $entity)
    {
        Gate::authorize('update', $entity);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:available,unavailable,draft',
            'unit_of_measurement' => 'sometimes|required|string|max:50',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'product_category_id' => 'sometimes|required|exists:product_categories,id',
        ]);

        if (isset($validated['unit_price']) && $validated['unit_price'] != $entity->unit_price) {
            $entity->price_change_reason = $request->input('price_change_reason', 'Price updated');
        }

        $entity->update($validated);

        $entity->load(['seller', 'category']);

        return (new ProductResource($entity))
            ->additional(['message' => __('messages.products.updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $entity)
    {
        Gate::authorize('delete', $entity);

        $entity->delete();

        return response()->json([
            'message' => 'Product deleted successfully.'
        ], 200);
    }
}
