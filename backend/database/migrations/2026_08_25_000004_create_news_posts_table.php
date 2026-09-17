<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('title', 120);
            $table->string('slug', 160)->unique();
            $table->string('summary', 300);
            $table->longText('content');
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('event_start_at')->nullable();
            $table->string('venue', 160)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
            $table->index(['sport_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
