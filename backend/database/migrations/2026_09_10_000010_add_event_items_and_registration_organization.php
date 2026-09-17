<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description', 500)->nullable();
            $table->decimal('registration_fee', 10, 2)->default(0);
            $table->decimal('early_bird_fee', 10, 2)->nullable();
            $table->timestamp('early_bird_ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['event_id', 'name']);
            $table->index(['event_id', 'is_active', 'sort_order']);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('organization', 150)->nullable()->after('contact_phone');
            $table->decimal('total_amount', 10, 2)->default(0)->after('organization');
        });

        Schema::create('event_registration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_registration_id')->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('event_item_id')->constrained('event_items')->restrictOnDelete();
            $table->decimal('unit_price', 10, 2);
            $table->timestamp('created_at')->nullable();
            $table->unique(['event_registration_id', 'event_item_id'], 'registration_item_unique');
        });

        // Keep existing events and registrations usable after upgrading.
        DB::table('events')->whereNull('deleted_at')->orderBy('id')->get()->each(function ($event): void {
            DB::table('event_items')->insert([
                'event_id' => $event->id,
                'name' => '一般項目',
                'registration_fee' => 0,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('event_registrations')->orderBy('id')->get()->each(function ($registration): void {
            $itemId = DB::table('event_items')->where('event_id', $registration->event_id)->value('id');
            if ($itemId) {
                DB::table('event_registration_items')->insert([
                    'event_registration_id' => $registration->id,
                    'event_item_id' => $itemId,
                    'unit_price' => 0,
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_items');
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['organization', 'total_amount']);
        });
        Schema::dropIfExists('event_items');
    }
};
