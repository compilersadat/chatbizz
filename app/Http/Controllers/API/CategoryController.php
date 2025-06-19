<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get categories with subcategories and products.
     */
    public function getCategoriesWithSubcategories(Request $request)
{
    $perPage = $request->get('per_page', 10);
    $merchantId = $request->user()->id;

    // Get categories, subcategories, and for each subcategory, only products NOT linked to this merchant
    $categories = ProductCategory::with([
        'subcategories.products' => function ($q) use ($merchantId) {
            // Filter products NOT belonging to the current merchant
            $q->whereDoesntHave('merchants', function ($q2) use ($merchantId) {
                $q2->where('merchants.id', $merchantId);
            });
        }
    ])->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $categories
    ]);
}

}

