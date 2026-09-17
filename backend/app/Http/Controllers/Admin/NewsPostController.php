<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\NewsPost;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsPostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = NewsPost::with('sport')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->latest('updated_at')->paginate(15)->withQueryString();

        return view('admin.news.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.news.form', ['post' => new NewsPost(), 'sports' => $this->sports()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['cover_path'] = $request->file('cover')?->store('news-covers', 'public');
        $post = NewsPost::create($data);
        $this->audit($request, 'news.created', $post, null, $post->toArray());

        return redirect()->route('admin.news.index')->with('success', '消息已建立。');
    }

    public function edit(NewsPost $newsPost): View
    {
        return view('admin.news.form', ['post' => $newsPost, 'sports' => $this->sports()]);
    }

    public function update(Request $request, NewsPost $newsPost): RedirectResponse
    {
        $before = $newsPost->toArray();
        $data = $this->validated($request);
        $data['updated_by'] = $request->user()->id;
        if ($request->hasFile('cover')) {
            if ($newsPost->cover_path) {
                Storage::disk('public')->delete($newsPost->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('news-covers', 'public');
        }
        $newsPost->update($data);
        $this->audit($request, 'news.updated', $newsPost, $before, $newsPost->fresh()->toArray());

        return redirect()->route('admin.news.index')->with('success', '消息已更新。');
    }

    public function destroy(Request $request, NewsPost $newsPost): RedirectResponse
    {
        $before = $newsPost->toArray();
        $newsPost->delete();
        $this->audit($request, 'news.deleted', $newsPost, $before, null);

        return redirect()->route('admin.news.index')->with('success', '消息已移至回收狀態。');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'sport_id' => ['required', 'exists:sports,id'],
            'title' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:300'],
            'content' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])],
            'event_start_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:160'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (in_array($data['status'], ['scheduled', 'published'], true) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'news-'.now()->format('YmdHis');
        $slug = $base;
        $counter = 2;
        while (NewsPost::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function sports()
    {
        return Sport::orderBy('sort_order')->orderBy('name')->get();
    }

    private function audit(Request $request, string $action, NewsPost $post, ?array $before, ?array $after): void
    {
        AdminAuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => NewsPost::class,
            'entity_id' => $post->id,
            'before_json' => $before,
            'after_json' => $after,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
