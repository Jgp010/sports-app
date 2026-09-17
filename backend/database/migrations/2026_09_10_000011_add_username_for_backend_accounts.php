<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('id');
            $table->string('email')->nullable()->change();
        });

        $firstAdminId = DB::table('users')->where('role', 'admin')->min('id');
        DB::table('users')->whereIn('role', ['admin', 'editor'])->orderBy('id')->get()
            ->each(function ($user) use ($firstAdminId): void {
                $base = $user->role === 'admin' && $user->id === $firstAdminId ? 'admin' : $user->role.'_'.$user->id;
                DB::table('users')->where('id', $user->id)->update(['username' => $base]);
            });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('email')->orderBy('id')->get()->each(function ($user): void {
            DB::table('users')->where('id', $user->id)->update(['email' => 'removed-'.$user->id.'@invalid.local']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });
    }
};
