<?php

namespace App\Console\Commands;

use App\Models\NewsPost;
use Illuminate\Console\Command;

class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';
    protected $description = 'Publish scheduled sports news whose publish time has arrived';

    public function handle(): int
    {
        $count = NewsPost::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published', 'updated_at' => now()]);

        $this->info("Published {$count} scheduled news posts.");

        return self::SUCCESS;
    }
}
