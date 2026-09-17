<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $registrations = EventRegistration::with(['event', 'user', 'items'])
            ->when($request->filled('event_id'), fn ($q) => $q->where('event_id', $request->integer('event_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = '%'.$request->string('q').'%';
                $q->where(fn ($sub) => $sub->where('registration_no', 'like', $keyword)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword)));
            })
            ->latest('registered_at')->paginate(20)->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'events' => Event::orderByDesc('event_start_at')->get(['id', 'title']),
        ]);
    }

    public function edit(EventRegistration $registration): View
    {
        return view('admin.registrations.edit', ['registration' => $registration->load(['event.items', 'user', 'items'])]);
    }

    public function update(Request $request, EventRegistration $registration): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['registered', 'cancelled', 'attended'])],
            'contact_phone' => ['required', 'string', 'max:30'],
            'organization' => ['required', 'string', 'max:150'],
            'emergency_contact_name' => ['required', 'string', 'max:80'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['cancelled_at'] = $data['status'] === 'cancelled' ? ($registration->cancelled_at ?? now()) : null;
        $selectedItems = $registration->event->items()->whereIn('id', $data['item_ids'])->get();
        abort_unless($selectedItems->count() === count($data['item_ids']), 422, '請重新選擇有效的賽事項目。');
        unset($data['item_ids']);
        $data['total_amount'] = $selectedItems->sum(fn ($item) => $item->currentPrice());
        DB::transaction(function () use ($registration, $selectedItems, $data): void {
            $registration->update($data);
            $registration->items()->sync($selectedItems->mapWithKeys(fn ($item) => [
                $item->id => ['unit_price' => $item->currentPrice(), 'created_at' => now()],
            ])->all());
        });

        return redirect()->route('admin.registrations.index')->with('success', '報名資料已更新。');
    }
}
