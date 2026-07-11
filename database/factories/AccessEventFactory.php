<?php

namespace Database\Factories;

use App\Enums\AccessResult;
use App\Enums\EventSeverity;
use App\Models\AccessEvent;
use App\Models\AccessPermission;
use App\Models\Door;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AccessEvent>
 */
class AccessEventFactory extends Factory
{
    /**
     * Define the model's default state: a badge/biometric attempt
     * during working hours within the last 14 days, mostly granted.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $permission = AccessPermission::where('type', 'permanent')->with('user')->inRandomOrder()->first();
        $door = Door::inRandomOrder()->first();
        $granted = fake()->boolean(88);
        $method = fake()->randomElement(['badge', 'badge', 'badge', 'face', 'fingerprint']);

        $happenedAt = Carbon::instance(fake()->dateTimeBetween('-14 days', '-1 day'))
            ->setTime(fake()->numberBetween(7, 19), fake()->numberBetween(0, 59), fake()->numberBetween(0, 59));

        return [
            'kind' => 'access',
            'user_id' => $permission?->user_id,
            'person_name' => $permission?->holderName() ?? fake()->name(),
            'badge_id' => $permission?->badge_id,
            'door_id' => $door?->id,
            'direction' => fake()->boolean(60) ? 'entry' : 'exit',
            'result' => $granted ? AccessResult::Granted : fake()->randomElement([
                AccessResult::Denied, AccessResult::Unauthorized, AccessResult::ExpiredBadge,
                AccessResult::FaceNotRecognized, AccessResult::FingerprintFailed,
            ]),
            'method' => $method,
            'device_id' => $door?->device_id,
            'camera_id' => $door?->camera_id,
            'face_confidence' => $method === 'face' && $granted ? fake()->numberBetween(85, 99) : null,
            'ip_address' => '192.168.2.'.fake()->numberBetween(10, 240),
            'detail' => $granted ? null : fake()->randomElement([
                'Access level below door requirement', 'Badge outside working schedule', 'Badge reported lost',
            ]),
            'happened_at' => $happenedAt,
        ];
    }

    /**
     * Recorded today (between midnight and now).
     */
    public function today(): static
    {
        return $this->state(fn () => [
            'happened_at' => fake()->dateTimeBetween(today(), 'now'),
        ]);
    }

    /**
     * A security incident for the timeline.
     */
    public function incident(): static
    {
        [$detail, $severity] = fake()->randomElement([
            ['Door forced open', EventSeverity::High],
            ['Door left open for more than 2 minutes', EventSeverity::Medium],
            ['Unknown face detected at reader', EventSeverity::High],
            ['Expired badge used repeatedly', EventSeverity::Low],
            ['Emergency exit triggered', EventSeverity::Critical],
            ['Reader synchronization failed', EventSeverity::Medium],
        ]);

        return $this->state(fn () => [
            'kind' => 'security',
            'person_name' => 'System',
            'result' => null,
            'direction' => null,
            'severity' => $severity,
            'detail' => $detail,
            'happened_at' => fake()->dateTimeBetween('-3 days', 'now'),
        ]);
    }
}
