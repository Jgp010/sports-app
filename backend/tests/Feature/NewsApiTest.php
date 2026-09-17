<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_news_is_returned(): void
    {
        $user = User::factory()->create();
        $sport = Sport::create(['name' => '籃球', 'slug' => 'basketball', 'is_active' => true]);
        NewsPost::create($this->postData($user, $sport, 'published', now()->subMinute(), 'published-post'));
        NewsPost::create($this->postData($user, $sport, 'draft', null, 'draft-post'));

        $this->getJson('/api/v1/news')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'published-post');
    }

    public function test_scheduled_news_is_hidden_until_publish_time(): void
    {
        $user = User::factory()->create();
        $sport = Sport::create(['name' => '足球', 'slug' => 'football', 'is_active' => true]);
        NewsPost::create($this->postData($user, $sport, 'scheduled', now()->addHour(), 'future-post'));

        $this->getJson('/api/v1/news')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/news/future-post')->assertNotFound();
    }

    private function postData(User $user, Sport $sport, string $status, $publishedAt, string $slug): array
    {
        return [
            'sport_id' => $sport->id,
            'title' => '測試消息',
            'slug' => $slug,
            'summary' => '測試摘要',
            'content' => '測試內文',
            'status' => $status,
            'published_at' => $publishedAt,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }
}
