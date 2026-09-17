<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->text('description');
            $table->string('venue', 180);
            $table->timestamp('event_start_at');
            $table->timestamp('event_end_at')->nullable();
            $table->timestamp('registration_open_at');
            $table->timestamp('registration_close_at');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'event_start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
