<?php

namespace Database\Factories;

use App\Enums\VisitAccessLevel;
use App\Enums\VisitDocumentType;
use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    private const DEPARTMENTS = [
        'Reception', 'Management', 'Human Resources', 'Finance', 'IT',
        'Operations', 'Security', 'Laboratory', 'Engineering', 'Legal',
    ];

    private const PURPOSES = [
        'Business meeting', 'Job interview', 'Equipment delivery', 'Maintenance intervention',
        'Contract signature', 'Security audit', 'Sales presentation', 'IT support visit',
        'Training session', 'Facility inspection',
    ];

    /**
     * Define the model's default state: a completed past visit.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $visitDate = Carbon::instance(fake()->dateTimeBetween('-30 days', '-1 day'))->startOfDay();
        $checkIn = $visitDate->copy()->setTime(fake()->numberBetween(8, 15), fake()->numberBetween(0, 59));
        $checkOut = $checkIn->copy()->addMinutes(fake()->numberBetween(15, 240));

        return [
            'full_name' => fake()->name(),
            'national_id' => strtoupper(fake()->bothify('?#######')),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->boolean(70) ? fake()->safeEmail() : null,
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'nationality' => fake()->randomElement(['Moroccan', 'Moroccan', 'Moroccan', 'French', 'Spanish', 'Senegalese']),
            'company' => fake()->boolean(75) ? fake()->company() : null,
            'host_user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'department' => fake()->randomElement(self::DEPARTMENTS),
            'purpose' => fake()->randomElement(self::PURPOSES),
            'visit_date' => $visitDate,
            'expected_check_in' => $checkIn->format('H:i'),
            'expected_duration_minutes' => fake()->randomElement([30, 60, 90, 120, 180]),
            'companions' => fake()->numberBetween(0, 3),
            'vehicle_plate' => fake()->boolean(40) ? strtoupper(fake()->bothify('####-?-#')) : null,
            'document_type' => fake()->randomElement(VisitDocumentType::cases()),
            'badge_number' => 'BDG-'.strtoupper(fake()->bothify('??####')),
            'bag_inspected' => fake()->boolean(60),
            'special_permission' => fake()->boolean(15),
            'access_level' => fake()->randomElement([
                VisitAccessLevel::Reception, VisitAccessLevel::Reception,
                VisitAccessLevel::Offices, VisitAccessLevel::Offices,
                VisitAccessLevel::Laboratory,
            ]),
            'blacklisted' => false,
            'security_notes' => fake()->boolean(25) ? fake()->sentence() : null,
            'checked_in_at' => $checkIn,
            'checked_out_at' => $checkOut,
            'status' => VisitStatus::Completed,
            'registered_by' => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    /**
     * Registered for today, not yet arrived.
     */
    public function expectedToday(): static
    {
        return $this->state(fn () => [
            'visit_date' => today(),
            'checked_in_at' => null,
            'checked_out_at' => null,
            'badge_number' => null,
            'status' => VisitStatus::Expected,
        ]);
    }

    /**
     * Currently inside the building, within the allowed duration.
     */
    public function insideNow(): static
    {
        return $this->state(fn () => [
            'visit_date' => today(),
            'checked_in_at' => now()->subMinutes(fake()->numberBetween(20, 90)),
            'checked_out_at' => null,
            'expected_duration_minutes' => 240,
            'status' => VisitStatus::Inside,
        ]);
    }

    /**
     * Inside, but past the allowed visit duration.
     */
    public function overstay(): static
    {
        return $this->state(fn () => [
            'visit_date' => today(),
            'checked_in_at' => now()->subHours(3),
            'checked_out_at' => null,
            'expected_duration_minutes' => 60,
            'status' => VisitStatus::Inside,
        ]);
    }

    /**
     * Checked in yesterday and never checked out.
     */
    public function forgotCheckOut(): static
    {
        return $this->state(fn () => [
            'visit_date' => today()->subDay(),
            'checked_in_at' => now()->subDay()->setTime(14, 30),
            'checked_out_at' => null,
            'status' => VisitStatus::Inside,
        ]);
    }

    /**
     * Visited earlier today and already left.
     */
    public function checkedOutToday(): static
    {
        return $this->state(function () {
            $checkIn = today()->setTime(fake()->numberBetween(8, 11), fake()->numberBetween(0, 59));

            return [
                'visit_date' => today(),
                'checked_in_at' => $checkIn,
                'checked_out_at' => $checkIn->copy()->addMinutes(fake()->numberBetween(20, 150)),
                'status' => VisitStatus::CheckedOut,
            ];
        });
    }

    /**
     * Refused at the gate.
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'checked_in_at' => null,
            'checked_out_at' => null,
            'badge_number' => null,
            'status' => VisitStatus::Rejected,
            'security_notes' => 'Identity could not be verified at reception.',
        ]);
    }

    /**
     * Flagged as blacklisted.
     */
    public function blacklisted(): static
    {
        return $this->state(fn () => [
            'blacklisted' => true,
            'security_notes' => 'Blacklisted after a previous incident — do not admit without supervisor approval.',
        ]);
    }
}
