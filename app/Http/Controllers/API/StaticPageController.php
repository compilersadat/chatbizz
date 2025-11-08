<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;

class StaticPageController extends Controller
{
    /**
     * Display the specified static page by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $page = StaticPage::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'updated_at' => optional($page->updated_at)->toIso8601String(),
            ],
        ]);
    }
}
