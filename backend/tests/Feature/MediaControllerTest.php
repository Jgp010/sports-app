<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    public function test_news_cover_can_be_served_without_public_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('news-covers/test.webp', 'image-content');

        $this->get('/storage/news-covers/test.webp')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_missing_news_cover_returns_not_found(): void
    {
        Storage::fake('public');
        $this->get('/storage/news-covers/missing.webp')->assertNotFound();
    }
}
