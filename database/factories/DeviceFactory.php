<?php

namespace Database\Factories;

use App\Enums\DeviceProtocol;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\SignalStrength;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DeviceType::cases());
        $number = fake()->unique()->numberBetween(1, 999);
        $status = fake()->randomElement([
            DeviceStatus::Online, DeviceStatus::Online, DeviceStatus::Online, DeviceStatus::Online,
            DeviceStatus::Offline, DeviceStatus::Maintenance,
        ]);
        $protocol = fake()->randomElement(DeviceProtocol::cases());
        $wired = in_array($protocol, [DeviceProtocol::Ethernet, DeviceProtocol::Poe, DeviceProtocol::Rs485], true);

        $shortName = str_replace([' Sensor', ' Detector', ' Recognition Terminal', ' Scanner', ' Reader'], '', $type->label());

        return [
            'device_id' => sprintf('DEV-%04d', $number),
            'name' => sprintf('%s-%03d', str_replace(' ', '-', $shortName), $number),
            'type' => $type,
            'brand' => fake()->randomElement(['Aqara', 'Bosch', 'Honeywell', 'Ajax', 'Hikvision', 'ZKTeco', 'Shelly']),
            'model' => strtoupper(fake()->bothify('??-####')),
            'protocol' => $protocol,
            'ip_address' => $wired || $protocol === DeviceProtocol::Wifi
                ? '192.168.'.fake()->numberBetween(5, 8).'.'.fake()->numberBetween(10, 250)
                : null,
            'mac_address' => strtoupper(fake()->macAddress()),
            'serial_number' => strtoupper(fake()->bothify('SN-########')),
            'firmware_version' => fake()->numerify('#.#.##'),
            'username' => 'admin',
            'password' => 'device-secret',
            'building' => fake()->randomElement(['HQ Building A', 'HQ Building B', 'Warehouse']),
            'floor' => fake()->randomElement(['Ground Floor', 'Floor 1', 'Floor 2', 'Basement']),
            'zone' => fake()->randomElement(['Zone North', 'Zone South', 'Zone East', 'Zone West']),
            'room' => fake()->optional(0.7)->randomElement(['Server Room', 'Reception', 'Storage', 'Office 1', 'Corridor', 'Lab']),
            'battery_level' => $wired ? null : fake()->numberBetween(5, 100),
            'signal_strength' => fake()->randomElement([
                SignalStrength::Excellent, SignalStrength::Excellent,
                SignalStrength::Good, SignalStrength::Weak,
            ]),
            'status' => $status,
            'last_seen' => $status === DeviceStatus::Online
                ? now()->subMinutes(fake()->numberBetween(0, 10))
                : fake()->dateTimeBetween('-10 days', '-2 hours'),
            'description' => fake()->optional(0.4)->sentence(8),
        ];
    }
}
