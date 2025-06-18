<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\MerchantProduct;

class ProductController extends Controller
{
    /**
     * Get all products from all merchants with search and pagination.
     */
    public function getAllProducts(Request $request)
    {
        // Get search term and pagination parameters from the request
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10); // Default to 10 if not provided

        // Start query for products
        $productsQuery = Product::query();

        // If a search term is provided, filter products based on name
        if ($search) {
            $productsQuery->where('name', 'like', "%{$search}%");
        }

        // Paginate the results
        $products = $productsQuery->paginate($perPage);

        // Return response
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
    
    public function getProductsByMerchant($merchantId, Request $request)
    {
        return MerchantProduct::all();
        $perPage = $request->input('per_page', 10);

        // Get categories that have products linked to this merchant
        $categories = ProductCategory::whereHas('products.merchants', function($q) use ($merchantId) {
            $q->where('merchants.id', $merchantId);
        })->with([
            'subcategories' => function($q) use ($merchantId) {
                $q->whereHas('products.merchants', function($q2) use ($merchantId) {
                    $q2->where('merchants.id', $merchantId);
                })->with(['products' => function($q3) use ($merchantId) {
                    $q3->whereHas('merchants', function($q4) use ($merchantId) {
                        $q4->where('merchants.id', $merchantId);
                    });
                }]);
            }
        ])->paginate($perPage);

        // Only include products that belong to the merchant (filter at each level)
        $data = $categories->through(function($category) use ($merchantId) {
            $subcategories = $category->subcategories->map(function($subcat) use ($merchantId) {
                $products = $subcat->products->filter(function($product) use ($merchantId) {
                    return $product->merchants->pluck('id')->contains($merchantId);
                })->values();
                $subcat->setRelation('products', $products);
                return $subcat;
            })->values();
            $category->setRelation('subcategories', $subcategories);
            return $category;
        });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}

