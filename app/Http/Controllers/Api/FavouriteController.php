<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FavouriteController extends Controller
{
    public function index(Request $request)
    {
        $entities = QueryBuilder::for(
            $request->user()->favouriteProducts()->where('status', 'available')
        )
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('location'),
                AllowedFilter::exact('category_id', 'product_category_id'),
            ])
            ->allowedSorts(['name', 'unit_price', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->with(['seller', 'category'])
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return ProductResource::collection($entities);
    }

    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();
        $result = $user->favouriteProducts()->toggle($product->id);

        $isFavourited = count($result['attached']) > 0;

        return response()->json([
            'message' => $isFavourited ? 'Added to favourites' : 'Removed from favourites',
            'is_favourited' => $isFavourited,
        ]);
    }
}
