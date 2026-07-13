<?php

namespace Database\Seeders;

use App\Enums\AccessLevel;
use App\Enums\AccessResult;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\DoorStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AccessEvent;
use App\Models\AccessPermission;
use App\Models\BiometricProfile;
use App\Models\BiometricVerification;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Door;
use App\Models\User;
use App\Models\Visit;
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
        // Exactly one account: the Super Admin.
        User::firstOrCreate(
            ['email' => 'admin@smartsecurity.test'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'phone' => '+212 600 000 000',
                'password' => 'password', // hashed by the model's "hashed" cast
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Active,
            ],
        );

        // Demo cameras so the Cameras Management module has data to browse.
        if (Camera::count() < 18) {
            Camera::factory()->count(18)->create();
        }

        // Demo IoT devices so the Devices Management module has data to browse.
        if (Device::count() < 22) {
            Device::factory()->count(22)->create();
        }

        // Demo visits covering every status the Visitors module handles.
        if (Visit::count() === 0) {
            Visit::factory()->count(22)->create();
            Visit::factory()->count(4)->expectedToday()->create();
            Visit::factory()->count(3)->insideNow()->create();
            Visit::factory()->overstay()->create();
            Visit::factory()->forgotCheckOut()->create();
            Visit::factory()->count(3)->checkedOutToday()->create();
            Visit::factory()->rejected()->create();
            Visit::factory()->blacklisted()->expectedToday()->create();

            // A returning visitor, so the show page has visible history.
            Visit::factory()->count(3)->create([
                'full_name' => 'Karim El Amrani',
                'national_id' => 'K1234567',
                'company' => 'Atlas Networks',
            ]);
            Visit::factory()->expectedToday()->create([
                'full_name' => 'Karim El Amrani',
                'national_id' => 'K1234567',
                'company' => 'Atlas Networks',
            ]);
        }

        // Biometric readers, enrollment profiles and verification logs.
        if (BiometricProfile::count() === 0) {
            $biometricTypes = [DeviceType::FaceTerminal, DeviceType::FingerprintScanner];

            if (Device::whereIn('type', $biometricTypes)->count() < 4) {
                Device::factory()->create(['name' => 'Main Entrance Face Terminal', 'type' => DeviceType::FaceTerminal, 'status' => DeviceStatus::Online, 'last_seen' => now()->subMinutes(3)]);
                Device::factory()->create(['name' => 'Server Room Face Terminal', 'type' => DeviceType::FaceTerminal, 'status' => DeviceStatus::Online, 'last_seen' => now()->subMinutes(10)]);
                Device::factory()->create(['name' => 'HR Fingerprint Scanner', 'type' => DeviceType::FingerprintScanner, 'status' => DeviceStatus::Offline, 'last_seen' => now()->subDays(2)]);
                Device::factory()->create(['name' => 'Lab Fingerprint Scanner', 'type' => DeviceType::FingerprintScanner, 'status' => DeviceStatus::Online, 'last_seen' => now()->subHour()]);
            }

            // One profile per existing user, so the table mirrors Users Management.
            User::whereDoesntHave('biometricProfile')
                ->limit(14)
                ->get()
                ->each(fn (User $user) => BiometricProfile::factory()->create(['user_id' => $user->id]));

            // Two weeks of verification traffic for the charts and logs.
            BiometricVerification::factory()->count(120)->create();
            BiometricVerification::factory()->count(12)->today()->create();
            BiometricVerification::factory()->count(2)->today()->unknownSubject()->create();
            BiometricVerification::factory()->today()->hardwareWarning()->create();

            // Repeated failures on one profile, to trigger the alert.
            $target = BiometricProfile::first();
            if ($target) {
                BiometricVerification::factory()->count(3)->today()->create([
                    'biometric_profile_id' => $target->id,
                    'subject_name' => $target->user->name,
                    'result' => \App\Enums\BiometricResult::Failed,
                    'detail' => 'Fingerprint mismatch',
                ]);
            }
        }

        // Doors, access permissions and two weeks of access traffic.
        if (Door::count() === 0) {
            $faceTerminal = Device::where('type', DeviceType::FaceTerminal)->value('id');
            $fingerprintScanner = Device::where('type', DeviceType::FingerprintScanner)->value('id');
            $cameras = Camera::pluck('id');

            $doorSpecs = [
                ['Main Entrance', 'Ground Floor', AccessLevel::Reception, DoorStatus::Closed, $faceTerminal],
                ['Reception Gate', 'Ground Floor', AccessLevel::Reception, DoorStatus::Open, null],
                ['Office Wing A', 'Floor 1', AccessLevel::Offices, DoorStatus::Closed, null],
                ['Office Wing B', 'Floor 1', AccessLevel::Offices, DoorStatus::Closed, null],
                ['Laboratory', 'Floor 2', AccessLevel::Laboratory, DoorStatus::Closed, $fingerprintScanner],
                ['Server Room', 'Floor 2', AccessLevel::ServerRoom, DoorStatus::Locked, $fingerprintScanner],
                ['Loading Dock', 'Ground Floor', AccessLevel::Offices, DoorStatus::Offline, null],
                ['Emergency Exit', 'Ground Floor', AccessLevel::FullAccess, DoorStatus::Closed, null],
            ];

            foreach ($doorSpecs as $i => [$name, $floor, $level, $status, $deviceId]) {
                Door::create([
                    'name' => $name,
                    'building' => 'HQ Building A',
                    'floor' => $floor,
                    'device_id' => $deviceId,
                    'camera_id' => $cameras->get($i % max($cameras->count(), 1)),
                    'required_access_level' => $level,
                    'status' => $status,
                    'last_activity_at' => now()->subMinutes(random_int(5, 300)),
                ]);
            }
        }

        if (AccessPermission::count() === 0) {
            $doorIds = Door::pluck('id');

            AccessPermission::factory()->count(10)->create()
                ->each(fn (AccessPermission $p) => $p->doors()->sync($doorIds->random(random_int(2, 4))->all()));
            AccessPermission::factory()->expired()->create()->doors()->sync($doorIds->random(2)->all());
            AccessPermission::factory()->disabled()->create()->doors()->sync($doorIds->random(2)->all());
        }

        if (AccessEvent::count() === 0) {
            AccessEvent::factory()->count(280)->create();
            AccessEvent::factory()->count(30)->today()->create();
            AccessEvent::factory()->count(6)->incident()->create();

            // Repeated denials by one badge holder, to trigger the alert.
            $repeat = AccessPermission::where('type', 'permanent')->first();
            if ($repeat) {
                AccessEvent::factory()->count(3)->today()->create([
                    'user_id' => $repeat->user_id,
                    'person_name' => $repeat->holderName(),
                    'badge_id' => $repeat->badge_id,
                    'result' => AccessResult::Denied,
                    'detail' => 'Access level below door requirement',
                ]);
            }

            // One expired-badge and one unauthorized attempt today.
            AccessEvent::factory()->today()->create(['result' => AccessResult::ExpiredBadge, 'detail' => 'Badge validity window is over']);
            AccessEvent::factory()->today()->create(['result' => AccessResult::Unauthorized, 'person_name' => 'Unknown Subject', 'user_id' => null, 'badge_id' => null, 'detail' => 'Badge not recognized by the system']);
        }

        // Two weeks of alerts across all types, plus fresh ones for today.
        if (\App\Models\Alert::count() === 0) {
            \App\Models\Alert::factory()->count(60)->create();
            \App\Models\Alert::factory()->count(10)->today()->create();
            \App\Models\Alert::factory()->count(2)->today()->criticalNew()->create();
        }

        // Two weeks of AI Security Bot findings, plus fresh ones for
        // today so the AI dashboard has live-looking data.
        if (\App\Models\AiAlert::count() === 0) {
            \App\Models\AiAlert::factory()->count(50)->create();
            \App\Models\AiAlert::factory()->count(12)->today()->create();
            \App\Models\AiAlert::factory()->count(2)->today()->criticalNew()->create();
        }
    }
}
