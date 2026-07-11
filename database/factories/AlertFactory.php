<?php

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Door;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state: an alert from the last
     * 14 days, most already handled by the team.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(Alert::TYPES);
        $status = fake()->randomElement([
            AlertStatus::New, AlertStatus::Pending, AlertStatus::Investigating,
            AlertStatus::Resolved, AlertStatus::Resolved, AlertStatus::Closed, AlertStatus::Ignored,
        ]);
        $happenedAt = Carbon::instance(fake()->dateTimeBetween('-14 days', 'now'));
        $resolved = in_array($status, [AlertStatus::Resolved, AlertStatus::Closed], true);

        return [
            'type' => $type,
            'severity' => fake()->randomElement([
                AlertSeverity::Critical,
                AlertSeverity::High, AlertSeverity::High,
                AlertSeverity::Medium, AlertSeverity::Medium,
                AlertSeverity::Low, AlertSeverity::Low,
                AlertSeverity::Information, AlertSeverity::Information,
            ]),
            'status' => $status,
            'description' => $type.' — '.fake()->sentence(8),
            'device_id' => fake()->boolean(50) ? Device::inRandomOrder()->value('id') : null,
            'camera_id' => fake()->boolean(35) ? Camera::inRandomOrder()->value('id') : null,
            'door_id' => fake()->boolean(35) ? Door::inRandomOrder()->value('id') : null,
            'user_id' => fake()->boolean(30) ? User::inRandomOrder()->value('id') : null,
            'building' => 'HQ Building A',
            'floor' => fake()->randomElement(['Ground Floor', 'Floor 1', 'Floor 2']),
            'ai_confidence' => fake()->boolean(45) ? fake()->numberBetween(62, 99) : null,
            'assigned_to' => $status === AlertStatus::New ? null
                : User::whereIn('role', [UserRole::Administrator, UserRole::SecurityOfficer])->inRandomOrder()->value('id'),
            'notes' => $resolved ? fake()->sentence() : null,
            'resolved_at' => $resolved ? $happenedAt->copy()->addMinutes(fake()->numberBetween(10, 600)) : null,
            'happened_at' => $happenedAt,
        ];
    }

    /**
     * Raised today, between midnight and now.
     */
    public function today(): static
    {
        return $this->state(fn () => [
            'happened_at' => fake()->dateTimeBetween(today(), 'now'),
        ]);
    }

    /**
     * A fresh critical alert nobody has touched yet.
     */
    public function criticalNew(): static
    {
        return $this->state(fn () => [
            'severity' => AlertSeverity::Critical,
            'status' => AlertStatus::New,
            'assigned_to' => null,
            'notes' => null,
            'resolved_at' => null,
        ]);
    }
}
