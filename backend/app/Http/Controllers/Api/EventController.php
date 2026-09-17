<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $events = Event::with(['sport:id,name,slug', 'items' => fn ($query) => $query->where('is_active', true)])
            ->withCount(['registrations as active_registrations_count' => fn ($query) => $query->where('status', 'registered')])
            ->published()->orderBy('event_start_at')->paginate(min(max((int) $request->input('per_page', 30), 1), 50));

        return response()->json([
            'success' => true,
            'data' => collect($events->items())->map(fn (Event $event) => self::serialize($event)),
            'meta' => ['page' => $events->currentPage(), 'per_page' => $events->perPage(), 'total' => $events->total(), 'last_page' => $events->lastPage()],
            'error' => null,
        ]);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status !== 'published') {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => 'EVENT_NOT_FOUND', 'message' => '查無賽事資料。'],
            ], 404);
        }

        $event->load(['sport:id,name,slug', 'items' => fn ($query) => $query->where('is_active', true)])
            ->loadCount(['registrations as active_registrations_count' => fn ($query) => $query->where('status', 'registered')]);

        return response()->json(['success' => true, 'data' => self::serialize($event), 'meta' => null, 'error' => null]);
    }

    public static function serialize(Event $event): array
    {
        return [
            'id' => $event->id, 'slug' => $event->slug, 'title' => $event->title, 'description' => $event->description,
            'venue' => $event->venue, 'sport' => $event->sport,
            'event_start_at' => $event->event_start_at->toIso8601String(), 'event_end_at' => $event->event_end_at?->toIso8601String(),
            'registration_open_at' => $event->registration_open_at->toIso8601String(), 'registration_close_at' => $event->registration_close_at->toIso8601String(),
            'capacity' => $event->capacity, 'registered_count' => $event->activeRegistrationsCount(),
            'registration_open' => $event->isRegistrationOpen(), 'status' => $event->status,
            'items' => $event->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'registration_fee' => (int) $item->registration_fee,
                'early_bird_fee' => $item->early_bird_fee === null ? null : (int) $item->early_bird_fee,
                'early_bird_ends_at' => $item->early_bird_ends_at?->toIso8601String(),
                'current_fee' => (int) $item->currentPrice(),
                'early_bird_active' => $item->early_bird_fee !== null && $item->early_bird_ends_at?->isFuture(),
            ])->values(),
        ];
    }
}
