<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Simplify the RBAC to exactly two accounts: one Super Admin and
     * one Viewer. Every other user is removed. All foreign keys to
     * users are cascadeOnDelete (biometric profiles, own permissions)
     * or nullOnDelete (event/alert/audit history), so history rows
     * survive with their user reference cleared.
     *
     * Raw DB queries only — the UserRole enum no longer contains the
     * removed cases, so Eloquent must not hydrate old rows here.
     */
    public function up(): void
    {
        // The single Super Admin.
        $superAdminId = DB::table('users')->where('email', 'admin@smartsecurity.test')->value('id');

        if ($superAdminId) {
            DB::table('users')->where('id', $superAdminId)->update(['role' => 'super_admin']);
        } else {
            $superAdminId = DB::table('users')->where('role', 'super_admin')->orderBy('id')->value('id');
        }

        // The single Viewer — create it when none exists.
        $viewerId = DB::table('users')->where('email', 'viewer@smartsecurity.test')->value('id')
            ?? DB::table('users')->where('role', 'viewer')->orderBy('id')->value('id');

        if ($viewerId) {
            DB::table('users')->where('id', $viewerId)->update(['role' => 'viewer']);
        } else {
            $viewerId = DB::table('users')->insertGetId([
                'first_name' => 'Vera',
                'last_name' => 'Viewer',
                'email' => 'viewer@smartsecurity.test',
                'phone' => '+212 611 111 111',
                'password' => Hash::make('password'),
                'role' => 'viewer',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $keep = array_filter([$superAdminId, $viewerId]);

        // Notifications are morphs (no FK) and sessions have no FK:
        // clean both up for the users about to be removed.
        DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereNotIn('notifiable_id', $keep)
            ->delete();

        DB::table('sessions')->whereNotNull('user_id')->whereNotIn('user_id', $keep)->delete();

        DB::table('users')->whereNotIn('id', $keep)->delete();
    }

    /**
     * Irreversible data migration: the removed accounts cannot be
     * restored. Down is intentionally a no-op.
     */
    public function down(): void
    {
        //
    }
};
