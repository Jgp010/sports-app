<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sport_id' => ['nullable', 'integer', 'exists:sports,id'],
            'q' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $posts = NewsPost::query()
            ->with('sport:id,name,slug')
            ->publiclyVisible()
            ->when($validated['sport_id'] ?? null, fn ($query, $sportId) => $query->where('sport_id', $sportId))
            ->when($validated['q'] ?? null, function ($query, $keyword) {
                $query->where(fn ($sub) => $sub
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('summary', 'like', "%{$keyword}%"));
            })
            ->latest('published_at')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'success' => true,
            'data' => collect($posts->items())->map(fn (NewsPost $post) => $this->serialize($post, false)),
            'meta' => [
                'page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
            'error' => null,
        ]);
    }

    public function show(NewsPost $newsPost): JsonResponse
    {
        abort_unless(
            in_array($newsPost->status, ['published', 'scheduled'], true)
            && $newsPost->published_at?->isPast(),
            404
        );

        $newsPost->load('sport:id,name,slug');

        return response()->json([
            'success' => true,
            'data' => $this->serialize($newsPost, true),
            'meta' => null,
            'error' => null,
        ]);
    }

    private function serialize(NewsPost $post, bool $withContent): array
    {
        return array_filter([
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'summary' => $post->summary,
            'content' => $withContent ? $post->content : null,
            'cover_url' => $post->cover_path ? route('media.news-cover', ['filename' => basename($post->cover_path)]) : null,
            'sport' => $post->sport,
            'event_start_at' => $post->event_start_at?->toIso8601String(),
            'venue' => $post->venue,
            'published_at' => $post->published_at?->toIso8601String(),
        ], fn ($value) => $value !== null);
    }
}
