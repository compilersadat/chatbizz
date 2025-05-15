<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MerchantCatagory;

class MerchantCategoryController extends Controller
{
    public function index()
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10); // default to 10

        $categories = MerchantCatagory::with(['merchants' => function ($query) use ($search) {
            $query->where('status', 1);

            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }
        }])
        ->when($search, function ($query) use ($search) {
            $query->where('cat_name', 'like', "%{$search}%");
        })
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
