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
                        'stock' => $pivot?->stock !== null ? (int)$pivot->stock : null,
                        'price' => $pivot?->price !== null ? (float)$pivot->price : null,
                        'discount_price' => $pivot?->discount !== null ? (float)$pivot->discount : null,
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
    
    public function searchProducts(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $search = trim((string) $request->input('search', ''));
        $catId = $request->input('cat_id');
        $subcatId = $request->input('subcat_id');
        $merchantId = $request->input('merchant_id');

        $query = MerchantProduct::query()
            ->select([
                'merchant_products.id',
                'merchant_products.product_id',
                'merchant_products.merchant_id',
                'merchant_products.stock',
                'merchant_products.price',
                'merchant_products.discount',
                'merchant_products.description',
                'products.title as product_title',
                'products.thumbnail as product_thumbnail',
                'products.status as product_status',
                'products.created_at as product_created_at',
                'products.updated_at as product_updated_at',
                'merchants.name as merchant_name',
            ])
            ->join('tbl_product as products', 'products.id', '=', 'merchant_products.product_id')
            ->join('merchants', 'merchants.id', '=', 'merchant_products.merchant_id')
            ->when($merchantId, function ($q) use ($merchantId) {
                $q->where('merchant_products.merchant_id', $merchantId);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where('products.title', 'like', '%' . $search . '%');
            })
            ->when($catId, function ($q) use ($catId) {
                $q->where('products.cat_id', $catId);
            })
            ->when($subcatId, function ($q) use ($subcatId) {
                $q->where('products.subcat_id', $subcatId);
            })
            ->orderBy('products.title');

        $products = $query->paginate($perPage);

        $products->setCollection(
            $products->getCollection()->map(function ($item) {
                return [
                    'id' => (int) $item->product_id,
                    'title' => $item->product_title,
                    'thumbnail' => $item->product_thumbnail,
                    'status' => $item->product_status,
                    'created_at' => $item->product_created_at,
                    'updated_at' => $item->product_updated_at,
                    'merchant_id' => (int) $item->merchant_id,
                    'merchant_name' => $item->merchant_name,
                    'stock' => $item->stock !== null ? (int) $item->stock : null,
                    'price' => $item->price !== null ? (float) $item->price : null,
                    'discount_price' => $item->discount !== null ? (float) $item->discount : null,
                    'description' => $item->description,
                ];
            })
        );

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
    

}
