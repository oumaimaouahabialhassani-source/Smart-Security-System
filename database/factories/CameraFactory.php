<?php

namespace Database\Factories;

use App\Enums\CameraBrand;
use App\Enums\CameraStatus;
use App\Enums\CameraType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Camera>
 */
class CameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $areas = ['Entrance', 'Lobby', 'Parking', 'Corridor', 'Server-Room', 'Loading-Dock', 'Reception', 'Perimeter', 'Stairwell', 'Elevator'];
        $area = fake()->randomElement($areas);
        $number = fake()->unique()->numberBetween(1, 99);
        $status = fake()->randomElement([
            CameraStatus::Online, CameraStatus::Online, CameraStatus::Online, CameraStatus::Online,
            CameraStatus::Offline, CameraStatus::Maintenance,
        ]);
        $ip = '192.168.'.fake()->numberBetween(1, 4).'.'.fake()->numberBetween(10, 250);

        return [
            'camera_id' => sprintf('CAM-%03d', $number),
            'name' => sprintf('%s-%02d', $area, $number),
            'brand' => fake()->randomElement(CameraBrand::cases()),
            'model' => strtoupper(fake()->bothify('DS-2CD####-?')),
            'type' => fake()->randomElement(CameraType::cases()),
            'ip_address' => $ip,
            'mac_address' => strtoupper(fake()->macAddress()),
            'username' => 'admin',
            'password' => 'camera-secret',
            'rtsp_url' => "rtsp://{$ip}:554/Streaming/Channels/101",
            'location' => str_replace('-', ' ', $area).' area',
            'building' => fake()->randomElement(['HQ Building A', 'HQ Building B', 'Warehouse']),
            'floor' => fake()->randomElement(['Ground Floor', 'Floor 1', 'Floor 2', 'Basement']),
            'zone' => fake()->randomElement(['Zone North', 'Zone South', 'Zone East', 'Zone West']),
            'resolution' => fake()->randomElement(['1280x720', '1920x1080', '2560x1440', '3840x2160']),
            'fps' => fake()->randomElement([15, 20, 25, 30]),
            'recording_enabled' => fake()->boolean(80),
            'status' => $status,
            'last_seen' => $status === CameraStatus::Online
                ? now()->subMinutes(fake()->numberBetween(0, 5))
                : fake()->dateTimeBetween('-7 days', '-1 hour'),
            'description' => fake()->optional(0.5)->sentence(10),
        ];
    }
}
