<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\StorageFallback;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{
    public function index(): JsonResponse
    {
        $ads = Banner::query()
            ->where('status', 1)
            ->latest()
            ->get()
            ->map(fn (Banner $ad): array => [
                'id' => $ad->id,
                'image' => $ad->img,
                'image_url' => StorageFallback::url($ad->img),
                'status' => $ad->status,
                'created_at' => $ad->created_at,
                'updated_at' => $ad->updated_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }
}
