<?php

namespace Database\Factories;

use App\Enums\BiometricStatus;
use App\Enums\DeviceType;
use App\Models\BiometricProfile;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiometricProfile>
 */
class BiometricProfileFactory extends Factory
{
    private const DEPARTMENTS = [
        'Reception', 'Management', 'Human Resources', 'Finance', 'IT',
        'Operations', 'Security', 'Laboratory', 'Engineering', 'Legal',
    ];

    private const POSITIONS = [
        'Security Analyst', 'Network Engineer', 'HR Specialist', 'Accountant',
        'Operations Manager', 'Lab Technician', 'Receptionist', 'Software Developer',
        'Facility Supervisor', 'Legal Advisor',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $face = fake()->boolean(75);
        $fingerprint = fake()->boolean(65);
        $iris = fake()->boolean(15);

        return [
            'user_id' => User::factory(),
            'department' => fake()->randomElement(self::DEPARTMENTS),
            'position' => fake()->randomElement(self::POSITIONS),
            'face_enrolled_at' => $face ? fake()->dateTimeBetween('-60 days', '-1 day') : null,
            'face_quality' => $face ? fake()->numberBetween(70, 99) : null,
            'fingerprint_enrolled_at' => $fingerprint ? fake()->dateTimeBetween('-60 days', '-1 day') : null,
            'fingerprint_finger' => $fingerprint ? fake()->randomElement(['Right Thumb', 'Right Index', 'Left Thumb', 'Left Index']) : null,
            'fingerprint_quality' => $fingerprint ? fake()->numberBetween(65, 99) : null,
            'iris_enrolled_at' => $iris ? fake()->dateTimeBetween('-60 days', '-1 day') : null,
            'assigned_device_id' => Device::whereIn('type', [DeviceType::FaceTerminal, DeviceType::FingerprintScanner])
                ->inRandomOrder()
                ->value('id'),
            'status' => ($face || $fingerprint || $iris) ? BiometricStatus::Active : BiometricStatus::Pending,
        ];
    }

    /**
     * Profile blocked from authenticating.
     */
    public function suspended(): static
    {
        return $this->state(fn () => ['status' => BiometricStatus::Suspended]);
    }
}
