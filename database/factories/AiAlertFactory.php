<?php

namespace Database\Factories;

use App\Enums\AiAlertStatus;
use App\Enums\AiRiskLevel;
use App\Models\AiAlert;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Door;
use App\Models\User;
use App\Models\Visit;
use App\Services\AiRiskAnalyzer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AiAlert>
 */
class AiAlertFactory extends Factory
{
    /**
     * Define the model's default state: an AI finding from the last
     * 14 days, analyzed by the real AiRiskAnalyzer so the sample data
     * carries authentic scores, explanations and recommendations.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventType = fake()->randomElement(AiAlert::EVENT_TYPES);
        $happenedAt = Carbon::instance(fake()->dateTimeBetween('-14 days', 'now'));

        $verdict = app(AiRiskAnalyzer::class)->analyze($eventType, [
            'happened_at' => $happenedAt,
            'repeat_count' => fake()->boolean(25) ? fake()->numberBetween(3, 6) : 0,
            'blacklisted' => $eventType === 'Blacklisted Visitor',
        ]);

        $status = fake()->randomElement([
            AiAlertStatus::New, AiAlertStatus::New, AiAlertStatus::Reviewing,
            AiAlertStatus::Actioned, AiAlertStatus::Resolved, AiAlertStatus::Resolved,
            AiAlertStatus::FalsePositive,
        ]);
        $handled = ! $status->isOpen();

        return [
            'event_type' => $eventType,
            'description' => $eventType.' — '.fake()->sentence(8),
            'risk_level' => $verdict['risk_level'],
            'risk_score' => $verdict['risk_score'],
            'analysis' => $verdict['analysis'],
            'recommendation' => $verdict['recommendation'],
            'camera_id' => fake()->boolean(40) ? Camera::inRandomOrder()->value('id') : null,
            'device_id' => fake()->boolean(35) ? Device::inRandomOrder()->value('id') : null,
            'door_id' => fake()->boolean(35) ? Door::inRandomOrder()->value('id') : null,
            'user_id' => fake()->boolean(30) ? User::inRandomOrder()->value('id') : null,
            'visit_id' => fake()->boolean(20) ? Visit::inRandomOrder()->value('id') : null,
            'building' => 'HQ Building A',
            'floor' => fake()->randomElement(['Ground Floor', 'Floor 1', 'Floor 2']),
            'status' => $status,
            'reviewed_by' => $handled ? User::inRandomOrder()->value('id') : null,
            'notes' => $handled ? fake()->sentence() : null,
            'resolved_at' => $handled ? $happenedAt->copy()->addMinutes(fake()->numberBetween(10, 600)) : null,
            'happened_at' => $happenedAt,
        ];
    }

    /**
     * Detected today, between midnight and now.
     */
    public function today(): static
    {
        return $this->state(fn () => [
            'happened_at' => fake()->dateTimeBetween(today(), 'now'),
        ]);
    }

    /**
     * A fresh critical finding nobody has reviewed yet.
     */
    public function criticalNew(): static
    {
        return $this->state(fn () => [
            'event_type' => 'Unauthorized Door Access',
            'risk_level' => AiRiskLevel::Critical,
            'risk_score' => fake()->numberBetween(85, 99),
            'recommendation' => \App\Enums\AiRecommendation::LockDoor,
            'status' => AiAlertStatus::New,
            'reviewed_by' => null,
            'notes' => null,
            'resolved_at' => null,
        ]);
    }
}
