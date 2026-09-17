<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->eventRegistrations()->with(['event.sport:id,name,slug', 'event.items', 'items'])
            ->latest('registered_at')->get()->map(fn (EventRegistration $registration) => $this->serialize($registration));

        return response()->json(['success' => true, 'data' => $items, 'meta' => null, 'error' => null]);
    }

    public function show(string $registrationNo): JsonResponse
    {
        $registration = EventRegistration::query()
            ->with(['event.sport:id,name,slug', 'event.items', 'items'])
            ->where('registration_no', $registrationNo)
            ->first();

        if (! $registration) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => 'REGISTRATION_NOT_FOUND', 'message' => '查無報名資料。'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serialize($registration),
            'meta' => null,
            'error' => null,
        ]);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate([
            'contact_phone' => ['required', 'string', 'max:30'],
            'organization' => ['required', 'string', 'max:150'],
            'emergency_contact_name' => ['required', 'string', 'max:80'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $registration = DB::transaction(function () use ($request, $event, $data) {
                $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedEvent->isRegistrationOpen(), 422, '目前不在報名期間或名額已滿。');

                $selectedItems = $lockedEvent->items()->where('is_active', true)
                    ->whereIn('id', $data['item_ids'])->lockForUpdate()->get();
                abort_unless($selectedItems->count() === count($data['item_ids']), 422, '請重新選擇有效的賽事項目。');
                $totalAmount = $selectedItems->sum(fn ($item) => $item->currentPrice());

                $registration = EventRegistration::create([
                    ...$data, 'event_id' => $lockedEvent->id, 'user_id' => $request->user()->id,
                    'registration_no' => 'R'.now()->format('YmdHis').strtoupper(Str::random(4)),
                    'status' => 'registered', 'registered_at' => now(), 'total_amount' => $totalAmount,
                ]);
                $registration->items()->attach($selectedItems->mapWithKeys(fn ($item) => [
                    $item->id => ['unit_price' => $item->currentPrice(), 'created_at' => now()],
                ])->all());

                return $registration;
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'success' => false, 'data' => null, 'meta' => null,
                'error' => ['code' => 'ALREADY_REGISTERED', 'message' => '您已報名此賽事。'],
            ], 409);
        }

        $registration->load(['event.sport:id,name,slug', 'event.items', 'items']);
        return response()->json(['success' => true, 'data' => $this->serialize($registration), 'meta' => null, 'error' => null], 201);
    }

    public function cancel(Request $request, EventRegistration $registration): JsonResponse
    {
        if ($registration->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'error' => ['code' => 'REGISTRATION_NOT_FOUND', 'message' => '查無報名資料。'],
            ], 404);
        }

        abort_if($registration->status !== 'registered', 422, '此報名無法取消。');
        abort_if($registration->event->event_start_at->isPast(), 422, '賽事已開始，無法取消。');
        $registration->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return response()->json(['success' => true, 'data' => null, 'meta' => null, 'error' => null]);
    }

    private function serialize(EventRegistration $registration): array
    {
        return [
            'id' => $registration->id, 'registration_no' => $registration->registration_no, 'status' => $registration->status,
            'contact_phone' => $registration->contact_phone, 'emergency_contact_name' => $registration->emergency_contact_name,
            'organization' => $registration->organization, 'total_amount' => (int) $registration->total_amount,
            'emergency_contact_phone' => $registration->emergency_contact_phone, 'notes' => $registration->notes,
            'registered_at' => $registration->registered_at->toIso8601String(), 'cancelled_at' => $registration->cancelled_at?->toIso8601String(),
            'items' => $registration->items->map(fn ($item) => [
                'id' => $item->id, 'name' => $item->name, 'unit_price' => (int) $item->pivot->unit_price,
            ])->values(),
            'event' => EventController::serialize($registration->event),
        ];
    }
}
