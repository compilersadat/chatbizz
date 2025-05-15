<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;

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
        // Get the search query from request (if any)
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10); // default to 10 if not provided

        // Fetch the merchant by ID, and load the products through the pivot table (merchant_products)
        $merchant = Merchant::find($merchantId);

        // If merchant does not exist, return a 404 response
        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found'
            ], 404);
        }

        // Query for products associated with the merchant
        $productsQuery = $merchant->merchantProducts()
            ->with('product') // eager load product details
            ->when($search, function ($query) use ($search) {
                // Search for products based on product name (or any other field)
                $query->whereHas('product', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            });

        // Paginate the results
        $products = $productsQuery->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}

