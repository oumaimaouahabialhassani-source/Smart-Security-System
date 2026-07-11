<?php

namespace Database\Factories;

use App\Enums\BiometricMethod;
use App\Enums\BiometricResult;
use App\Enums\DeviceType;
use App\Models\BiometricProfile;
use App\Models\BiometricVerification;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiometricVerification>
 */
class BiometricVerificationFactory extends Factory
{
    /**
     * Define the model's default state: an attempt by a random
     * enrolled profile within the last 14 days, mostly successful.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profile = BiometricProfile::inRandomOrder()->first();
        $success = fake()->boolean(85);

        return [
            'biometric_profile_id' => $profile?->id,
            'subject_name' => $profile?->user?->name ?? fake()->name(),
            'method' => fake()->randomElement([
                BiometricMethod::Face, BiometricMethod::Face,
                BiometricMethod::Fingerprint, BiometricMethod::Fingerprint,
                BiometricMethod::Iris,
            ]),
            'device_id' => $profile?->assigned_device_id
                ?? Device::whereIn('type', [DeviceType::FaceTerminal, DeviceType::FingerprintScanner])->inRandomOrder()->value('id'),
            'result' => $success ? BiometricResult::Success : BiometricResult::Failed,
            'detail' => $success ? null : fake()->randomElement([
                'Fingerprint mismatch', 'Face match below threshold', 'Timeout during capture',
            ]),
            'duration_ms' => fake()->numberBetween(250, 1200),
            'ip_address' => '192.168.1.'.fake()->numberBetween(20, 250),
            'happened_at' => fake()->dateTimeBetween('-14 days', 'now'),
        ];
    }

    /**
     * An attempt by a subject the system does not recognize.
     */
    public function unknownSubject(): static
    {
        return $this->state(fn () => [
            'biometric_profile_id' => null,
            'subject_name' => 'Unknown Subject',
            'method' => BiometricMethod::Face,
            'result' => BiometricResult::Failed,
            'detail' => 'Unknown face detected',
        ]);
    }

    /**
     * A hardware or synchronization problem reported by a reader.
     */
    public function hardwareWarning(): static
    {
        return $this->state(fn () => [
            'result' => BiometricResult::Warning,
            'detail' => fake()->randomElement([
                'Camera error — lens obstructed', 'Scanner error — poor quality read', 'Synchronization failed',
            ]),
        ]);
    }

    /**
     * Recorded today.
     */
    public function today(): static
    {
        return $this->state(fn () => [
            'happened_at' => fake()->dateTimeBetween(today(), 'now'),
        ]);
    }
}
