<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->after('id')->nullable();
            $table->string('last_name', 100)->after('first_name')->nullable();
            $table->string('phone', 30)->after('email')->nullable();
            $table->string('avatar')->after('password')->nullable();
            $table->string('role', 30)->after('avatar')->default('employee')->index();
            $table->string('status', 20)->after('role')->default('active')->index();
            $table->timestamp('last_login')->after('status')->nullable();
        });

        // Split the legacy "name" column into first/last before dropping it.
        DB::table('users')->orderBy('id')->each(function ($user) {
            $parts = preg_split('/\s+/', trim($user->name), 2);

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0] ?? 'Unknown',
                'last_name' => $parts[1] ?? '',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->nullable();
        });

        DB::table('users')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'name' => trim($user->first_name.' '.$user->last_name),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'avatar', 'role', 'status', 'last_login']);
        });
    }
};
