<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('phone');
            $table->string('gender', 20)->nullable()->after('birth_date');
            $table->string('address', 255)->nullable()->after('gender');
            $table->string('sso_provider', 60)->nullable()->after('address');
            $table->string('sso_subject', 191)->nullable()->after('sso_provider');
            $table->text('sso_credential')->nullable()->after('sso_subject');
            $table->unique(['sso_provider', 'sso_subject']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sso_provider', 'sso_subject']);
            $table->dropColumn(['phone', 'birth_date', 'gender', 'address', 'sso_provider', 'sso_subject', 'sso_credential']);
        });
    }
};
