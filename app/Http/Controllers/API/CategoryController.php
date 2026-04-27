<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get categories with subcategories.
     */
    public function getCategoriesWithSubcategories(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $categories = ProductCategory::with('subcategories')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

}
