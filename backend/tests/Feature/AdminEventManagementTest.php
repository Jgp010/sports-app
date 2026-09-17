<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventItem;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminEventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_an_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $sport = Sport::create(['name' => '跑步', 'slug' => 'running', 'is_active' => true]);
        $payload = [
            'sport_id' => $sport->id, 'title' => '台北路跑', 'description' => '測試賽事', 'venue' => '台北市',
            'event_start_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'registration_open_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'registration_close_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'capacity' => 50, 'status' => 'published',
            'items' => [['name' => '10 公里組', 'registration_fee' => 900, 'early_bird_fee' => 700, 'early_bird_ends_at' => now()->addDays(3)->format('Y-m-d H:i:s'), 'is_active' => 1]],
        ];

        $this->actingAs($admin)->post('/admin/events', $payload)->assertRedirect('/admin/events');
        $event = Event::firstOrFail();
        $this->assertSame('台北路跑', $event->title);
        $this->assertSame('10 公里組', $event->items()->firstOrFail()->name);

        $this->actingAs($admin)->put('/admin/events/'.$event->slug, [...$payload, 'title' => '台北路跑更新'])->assertRedirect('/admin/events');
        $this->assertSame('台北路跑更新', $event->fresh()->title);

        $this->actingAs($admin)->delete('/admin/events/'.$event->slug)->assertRedirect('/admin/events');
        $this->assertSoftDeleted($event);
    }

    public function test_admin_can_view_members_and_edit_registration(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $member = User::factory()->create(['role' => 'member', 'phone' => '0911222333']);
        $sport = Sport::create(['name' => '籃球', 'slug' => 'basketball', 'is_active' => true]);
        $event = Event::create([
            'sport_id' => $sport->id, 'title' => '會員測試賽事', 'slug' => 'member-event', 'description' => '內容', 'venue' => '台北',
            'event_start_at' => now()->addDays(10), 'registration_open_at' => now()->subDay(), 'registration_close_at' => now()->addDays(5),
            'status' => 'published', 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $registration = EventRegistration::create([
            'event_id' => $event->id, 'user_id' => $member->id, 'registration_no' => 'RTEST0001', 'status' => 'registered',
            'contact_phone' => '0911222333', 'organization' => '測試單位', 'emergency_contact_name' => '王先生',
            'emergency_contact_phone' => '0922333444', 'registered_at' => now(),
        ]);
        $eventItem = EventItem::create(['event_id' => $event->id, 'name' => '一般組', 'registration_fee' => 500, 'is_active' => true]);
        $registration->items()->attach($eventItem->id, ['unit_price' => 500, 'created_at' => now()]);

        $this->actingAs($admin)->get('/admin/members')->assertOk()->assertSee($member->email);
        $this->actingAs($admin)->get('/admin/registrations')->assertOk()->assertSee('RTEST0001');
        $this->actingAs($admin)->put('/admin/registrations/'.$registration->id, [
            'status' => 'attended', 'contact_phone' => '0911222333', 'organization' => '測試單位',
            'emergency_contact_name' => '王先生', 'emergency_contact_phone' => '0922333444', 'item_ids' => [$eventItem->id],
        ])->assertRedirect('/admin/registrations');
        $this->assertSame('attended', $registration->fresh()->status);
    }

    public function test_sso_credential_is_encrypted_at_rest(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $member->update(['sso_provider' => 'future-sso', 'sso_subject' => 'subject-001', 'sso_credential' => 'sensitive-credential']);

        $this->assertNotSame('sensitive-credential', DB::table('users')->where('id', $member->id)->value('sso_credential'));
        $this->assertSame('sensitive-credential', $member->fresh()->sso_credential);
    }
}
