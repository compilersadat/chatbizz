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
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        $page = $request->get('page', 1); // Default to the first page

        // Get categories with subcategories and products, paginated
        $categories = ProductCategory::with(['subcategories.products'])
            ->paginate($perPage); // Paginate categories

        // Return the paginated data as JSON
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);

    }
}

