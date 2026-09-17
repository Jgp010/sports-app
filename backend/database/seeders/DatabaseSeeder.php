<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\NewsPost;
use App\Models\Sport;
use App\Models\Event;
use App\Models\EventRegistration;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['username' => env('ADMIN_USERNAME', 'admin')],
            ['email' => null, 'name' => '系統管理員', 'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')), 'role' => 'admin', 'is_active' => true]
        );

        $sports = collect([
            ['name' => '籃球', 'slug' => 'basketball', 'sort_order' => 10],
            ['name' => '棒球', 'slug' => 'baseball', 'sort_order' => 20],
            ['name' => '足球', 'slug' => 'football', 'sort_order' => 30],
        ])->map(fn ($item) => Sport::updateOrCreate(['slug' => $item['slug']], [...$item, 'is_active' => true]));

        NewsPost::updateOrCreate(['slug' => 'welcome-to-sports-desk'], [
            'sport_id' => $sports->first()->id,
            'title' => '體育賽事消息 App MVP 正式啟動',
            'summary' => '這是一筆可供 Android App 與 API 測試的示範消息。',
            'content' => "歡迎使用體育賽事消息 App。\n\n管理員可以在後台新增、排程、發布與下架消息，Android App 會讀取公開 API 顯示最新內容。",
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $member = User::updateOrCreate(
            ['email' => 'member@example.com'],
            ['name' => '示範會員', 'phone' => '0912345678', 'password' => Hash::make('Member123!'), 'role' => 'member', 'is_active' => true]
        );

        $event = Event::updateOrCreate(['slug' => 'taipei-basketball-cup'], [
            'sport_id' => $sports->first()->id,
            'title' => '台北城市籃球交流賽',
            'description' => '提供 App 賽事列表與報名流程測試使用的示範賽事。',
            'venue' => '台北市立體育館',
            'event_start_at' => now()->addDays(14)->setTime(9, 0),
            'event_end_at' => now()->addDays(14)->setTime(17, 0),
            'registration_open_at' => now()->subDay(),
            'registration_close_at' => now()->addDays(10),
            'capacity' => 100,
            'status' => 'published',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
