<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\JsonResponse;

class StaticPageController extends Controller
{
    /**
     * Display all static pages.
     */
    public function index(): JsonResponse
    {
        $pages = StaticPage::query()
            ->orderBy('title')
            ->get(['title', 'slug', 'content', 'updated_at']);

        return response()->json([
            'data' => $pages->map(function (StaticPage $page) {
                return [
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content' => $page->content,
                    'updated_at' => optional($page->updated_at)->toIso8601String(),
                ];
            }),
        ]);
    }

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
