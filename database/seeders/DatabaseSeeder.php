<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Camera;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Idempotent: safe to run repeatedly (locally, in Docker, in CI).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@smartsecurity.test'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'phone' => '+212 600 000 000',
                'password' => 'password', // hashed by the model's "hashed" cast
                'role' => UserRole::Administrator,
                'status' => UserStatus::Active,
            ],
        );

        // Demo users so the Users Management table has data to browse.
        if (User::count() < 15) {
            User::factory()->count(15)->create();
        }

        // Demo cameras so the Cameras Management module has data to browse.
        if (Camera::count() < 18) {
            Camera::factory()->count(18)->create();
        }

        // Demo IoT devices so the Devices Management module has data to browse.
        if (Device::count() < 22) {
            Device::factory()->count(22)->create();
        }
    }
}
