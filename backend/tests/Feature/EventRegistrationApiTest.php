<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventItem;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_register_and_view_own_registration(): void
    {
        [$event] = $this->eventAndUser();
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'member@example.com', 'password' => 'password123'])
            ->assertOk()->json('data.token');

        $this->withToken($token)->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))
            ->assertCreated()->assertJsonPath('data.status', 'registered')->assertJsonPath('data.organization', '測試單位')
            ->assertJsonPath('data.total_amount', 600)->assertJsonPath('data.items.0.unit_price', 600);
        $this->withToken($token)->getJson('/api/v1/me/registrations')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.event.slug', $event->slug);
    }

    public function test_member_can_find_own_registration_by_registration_number(): void
    {
        [$event] = $this->eventAndUser();
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'member@example.com', 'password' => 'password123'])
            ->assertOk()->json('data.token');
        $registrationNo = $this->withToken($token)
            ->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))
            ->assertCreated()->json('data.registration_no');

        $this->getJson('/api/v1/registrations/'.$registrationNo)
            ->assertOk()
            ->assertJsonPath('data.registration_no', $registrationNo)
            ->assertJsonPath('data.event.slug', $event->slug);
    }

    public function test_registration_lookup_does_not_require_login(): void
    {
        [$event] = $this->eventAndUser();
        $firstToken = $this->postJson('/api/v1/auth/login', ['email' => 'member@example.com', 'password' => 'password123'])
            ->assertOk()->json('data.token');
        $registrationNo = $this->withToken($firstToken)
            ->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))
            ->assertCreated()->json('data.registration_no');

        $this->getJson('/api/v1/registrations/'.$registrationNo)
            ->assertOk()
            ->assertJsonPath('data.registration_no', $registrationNo);

        $this->getJson('/api/v1/registrations/NOT-FOUND')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'REGISTRATION_NOT_FOUND');
    }

    public function test_registration_requires_login(): void
    {
        [$event] = $this->eventAndUser();
        $this->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))
            ->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_member_cannot_register_twice(): void
    {
        [$event] = $this->eventAndUser();
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'member@example.com', 'password' => 'password123'])->json('data.token');
        $this->withToken($token)->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))->assertCreated();
        $this->withToken($token)->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))
            ->assertConflict()->assertJsonPath('error.code', 'ALREADY_REGISTERED');
    }

    public function test_taiwan_timezone_is_returned_by_api(): void
    {
        [$event] = $this->eventAndUser();
        $this->getJson('/api/v1/events/'.$event->slug)->assertOk()->assertJsonPath('data.event_start_at', $event->event_start_at->toIso8601String());
        $this->assertStringEndsWith('+08:00', $this->getJson('/api/v1/health')->json('data.time'));
    }

    public function test_event_fees_are_returned_as_integers(): void
    {
        [$event] = $this->eventAndUser();

        $response = $this->getJson('/api/v1/events/'.$event->slug)->assertOk();
        $this->assertIsInt($response->json('data.items.0.registration_fee'));
        $this->assertIsInt($response->json('data.items.0.early_bird_fee'));
        $this->assertIsInt($response->json('data.items.0.current_fee'));
    }

    public function test_unpublished_event_returns_a_fixed_chinese_error(): void
    {
        [$event] = $this->eventAndUser();
        $event->update(['status' => 'draft']);

        $this->getJson('/api/v1/events/'.$event->slug)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'EVENT_NOT_FOUND')
            ->assertJsonPath('error.message', '查無賽事資料。');
    }

    public function test_registration_is_rejected_outside_registration_period(): void
    {
        [$event] = $this->eventAndUser();
        $event->update(['registration_open_at' => now()->subDays(2), 'registration_close_at' => now()->subDay()]);
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'member@example.com', 'password' => 'password123'])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/events/'.$event->slug.'/registrations', $this->registrationPayload($event))
            ->assertUnprocessable();
    }

    public function test_member_can_update_basic_profile(): void
    {
        $this->eventAndUser();
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'member@example.com', 'password' => 'password123'])->json('data.token');

        $this->withToken($token)->putJson('/api/v1/me', [
            'name' => '更新會員', 'email' => 'member@example.com', 'phone' => '0900000000',
            'birth_date' => '2000-01-01', 'gender' => 'undisclosed', 'address' => '台北市',
        ])->assertOk()->assertJsonPath('data.name', '更新會員')->assertJsonPath('data.phone', '0900000000');
    }

    private function eventAndUser(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'member@example.com', 'password' => 'password123', 'role' => 'member']);
        $sport = Sport::create(['name' => '籃球', 'slug' => 'basketball', 'is_active' => true]);
        $event = Event::create([
            'sport_id' => $sport->id, 'title' => '測試賽事', 'slug' => 'test-event', 'description' => '內容', 'venue' => '台北',
            'event_start_at' => now()->addDays(5), 'registration_open_at' => now()->subHour(), 'registration_close_at' => now()->addDay(),
            'capacity' => 10, 'status' => 'published', 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        EventItem::create(['event_id' => $event->id, 'name' => '跳馬', 'registration_fee' => 800, 'early_bird_fee' => 600, 'early_bird_ends_at' => now()->addHour(), 'is_active' => true]);

        return [$event, $member];
    }

    private function registrationPayload(Event $event): array
    {
        return [
            'contact_phone' => '0911222333', 'organization' => '測試單位',
            'emergency_contact_name' => '緊急聯絡人', 'emergency_contact_phone' => '0922333444',
            'item_ids' => [$event->items()->firstOrFail()->id],
        ];
    }
}
