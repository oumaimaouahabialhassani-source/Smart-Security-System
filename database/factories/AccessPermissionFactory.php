<?php

namespace Database\Factories;

use App\Enums\AccessLevel;
use App\Models\AccessPermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessPermission>
 */
class AccessPermissionFactory extends Factory
{
    private const DEPARTMENTS = [
        'Reception', 'Management', 'Human Resources', 'Finance', 'IT',
        'Operations', 'Security', 'Laboratory', 'Engineering', 'Legal',
    ];

    private const POSITIONS = [
        'Security Analyst', 'Network Engineer', 'HR Specialist', 'Accountant',
        'Operations Manager', 'Lab Technician', 'Receptionist', 'Software Developer',
    ];

    /**
     * Define the model's default state: an active employee badge.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'badge_id' => strtoupper(fake()->unique()->bothify('BDG-####')),
            'department' => fake()->randomElement(self::DEPARTMENTS),
            'position' => fake()->randomElement(self::POSITIONS),
            'access_level' => fake()->randomElement([
                AccessLevel::Reception, AccessLevel::Offices, AccessLevel::Offices,
                AccessLevel::Laboratory, AccessLevel::ServerRoom,
            ]),
            'building' => 'HQ Building A',
            'floor' => fake()->randomElement(['Ground Floor', 'Floor 1', 'Floor 2']),
            'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'start_time' => '08:00',
            'end_time' => '18:00',
            'valid_from' => fake()->dateTimeBetween('-6 months', '-1 month')->format('Y-m-d'),
            'valid_until' => fake()->boolean(70) ? null : fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'notes' => fake()->boolean(20) ? fake()->sentence() : null,
            'active' => true,
            'type' => 'permanent',
        ];
    }

    /**
     * Validity window already over.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'valid_until' => fake()->dateTimeBetween('-2 months', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * Badge switched off by an administrator.
     */
    public function disabled(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
