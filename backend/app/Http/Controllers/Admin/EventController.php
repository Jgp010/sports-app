<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::with('sport')->withCount('registrations')
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('event_start_at')->paginate(15)->withQueryString();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event(), 'sports' => $this->sports()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        DB::transaction(function () use ($data): void {
            $event = Event::create(collect($data)->except('items')->all());
            $this->saveItems($event, $data['items']);
        });

        return redirect()->route('admin.events.index')->with('success', '賽事已新增。');
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', ['event' => $event->load('items'), 'sports' => $this->sports()]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validated($request);
        $data['updated_by'] = $request->user()->id;
        DB::transaction(function () use ($event, $data): void {
            $event->update(collect($data)->except('items')->all());
            $this->saveItems($event, $data['items']);
        });

        return redirect()->route('admin.events.index')->with('success', '賽事已更新。');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', '賽事已刪除。');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'sport_id' => ['required', 'exists:sports,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'venue' => ['required', 'string', 'max:180'],
            'event_start_at' => ['required', 'date'],
            'event_end_at' => ['nullable', 'date', 'after:event_start_at'],
            'registration_open_at' => ['required', 'date'],
            'registration_close_at' => ['required', 'date', 'after:registration_open_at', 'before_or_equal:event_start_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'status' => ['required', Rule::in(['draft', 'published', 'cancelled'])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:120', 'distinct'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.registration_fee' => ['required', 'integer', 'min:0', 'max:99999999'],
            'items.*.early_bird_fee' => ['nullable', 'required_with:items.*.early_bird_ends_at', 'integer', 'min:0', 'max:99999999'],
            'items.*.early_bird_ends_at' => ['nullable', 'required_with:items.*.early_bird_fee', 'date', 'before_or_equal:registration_close_at'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ], [
            'registration_close_at.after' => '報名截止時間必須晚於報名開放時間。',
            'registration_close_at.before_or_equal' => '報名截止時間不可晚於賽事開始時間。',
            'event_end_at.after' => '賽事結束時間必須晚於開始時間。',
            'items.required' => '請至少建立一個賽事項目。',
            'items.*.name.required' => '第 :position 個賽事項目必須填寫名稱。',
            'items.*.registration_fee.required' => '第 :position 個賽事項目必須填寫報名金額。',
            'items.*.early_bird_fee.required_with' => '第 :position 個賽事項目填寫早鳥截止時間時，也必須填寫早鳥金額。',
            'items.*.early_bird_ends_at.required_with' => '第 :position 個賽事項目填寫早鳥金額時，也必須填寫早鳥截止時間。',
            'items.*.early_bird_ends_at.before_or_equal' => '第 :position 個賽事項目的早鳥截止時間不可晚於整場賽事的報名截止時間。',
        ]);
    }

    private function saveItems(Event $event, array $items): void
    {
        $retainedIds = [];
        foreach ($items as $index => $data) {
            $id = $data['id'] ?? null;
            $item = $id
                ? $event->items()->whereKey($id)->firstOrFail()
                : ($event->items()->where('name', $data['name'])->first() ?? $event->items()->make());
            $item->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'registration_fee' => $data['registration_fee'],
                'early_bird_fee' => $data['early_bird_fee'] ?? null,
                'early_bird_ends_at' => $data['early_bird_ends_at'] ?? null,
                'sort_order' => $index,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);
            $item->save();
            $retainedIds[] = $item->id;
        }

        $event->items()->whereNotIn('id', $retainedIds)->get()->each(function ($item): void {
            if ($item->registrations()->exists()) {
                $item->update(['is_active' => false]);
            } else {
                $item->delete();
            }
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event-'.now()->format('YmdHis');
        $slug = $base;
        $counter = 2;
        while (Event::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function sports()
    {
        return Sport::orderBy('sort_order')->orderBy('name')->get();
    }
}
