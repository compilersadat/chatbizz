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

    public function getMerchantProducts(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $merchantId = $request->user()->id;
        // Get categories with subcategories & products filtered by this merchant
        $categories = ProductCategory::whereHas('products.merchants', function($q) use ($merchantId) {
                $q->where('merchants.id', $merchantId);
            })
            ->with(['subcategories.products' => function($q) use ($merchantId) {
                $q->whereHas('merchants', function($q2) use ($merchantId) {
                    $q2->where('merchants.id', $merchantId);
                })->with(['merchants' => function($q3) use ($merchantId) {
                    $q3->where('merchants.id', $merchantId);
                }]);
            }])
            ->paginate($perPage);
    
        // Remove merchants array and only include pivot fields
        $data = $categories->through(function($category) use ($merchantId) {
            $category->subcategories->transform(function($subcat) use ($merchantId) {
                $subcat->products->transform(function($product) use ($merchantId) {
                    // Only get the current merchant's pivot data
                    $pivot = $product->merchants->first()?->pivot;
                    // Prepare product data
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'thumbnail' => $product->thumbnail,
                        'status' => $product->status,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        // Merchant-specific fields
                        'stock' => $pivot?->stock,
                        'price' => $pivot?->price,
                        'discount_price' => $pivot?->discount,
                        'description' => $pivot?->description,
                    ];
                });
                return $subcat;
            });
            return $category;
        });
    
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
}

