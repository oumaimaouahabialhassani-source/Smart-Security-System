<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Single-account mode for the presentation: only the Super Admin
     * remains. All foreign keys to users are cascadeOnDelete or
     * nullOnDelete, so history rows survive with cleared references.
     */
    public function up(): void
    {
        $superAdminId = DB::table('users')->where('email', 'admin@smartsecurity.test')->value('id')
            ?? DB::table('users')->where('role', 'super_admin')->orderBy('id')->value('id');

        if (! $superAdminId) {
            return; // fresh database — the seeder creates the account
        }

        // Morph notifications and sessions have no FK: clean up first.
        DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('notifiable_id', '!=', $superAdminId)
            ->delete();

        DB::table('sessions')->whereNotNull('user_id')->where('user_id', '!=', $superAdminId)->delete();

        DB::table('users')->where('id', '!=', $superAdminId)->delete();
    }

    /**
     * Irreversible data migration — down is intentionally a no-op.
     */
    public function down(): void
    {
        //
    }
};
