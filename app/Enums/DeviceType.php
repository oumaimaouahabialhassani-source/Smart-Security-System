<?php

namespace App\Enums;

enum DeviceType: string
{
    case MotionSensor = 'motion_sensor';
    case DoorSensor = 'door_sensor';
    case WindowSensor = 'window_sensor';
    case SmokeDetector = 'smoke_detector';
    case GasSensor = 'gas_sensor';
    case WaterLeakSensor = 'water_leak_sensor';
    case TemperatureSensor = 'temperature_sensor';
    case HumiditySensor = 'humidity_sensor';
    case SmartLock = 'smart_lock';
    case RfidReader = 'rfid_reader';
    case FingerprintScanner = 'fingerprint_scanner';
    case FaceTerminal = 'face_terminal';
    case AlarmSiren = 'alarm_siren';
    case SmartLight = 'smart_light';

    public function label(): string
    {
        return match ($this) {
            self::MotionSensor => 'Motion Sensor',
            self::DoorSensor => 'Door Sensor',
            self::WindowSensor => 'Window Sensor',
            self::SmokeDetector => 'Smoke Detector',
            self::GasSensor => 'Gas Sensor',
            self::WaterLeakSensor => 'Water Leak Sensor',
            self::TemperatureSensor => 'Temperature Sensor',
            self::HumiditySensor => 'Humidity Sensor',
            self::SmartLock => 'Smart Lock',
            self::RfidReader => 'RFID Reader',
            self::FingerprintScanner => 'Fingerprint Scanner',
            self::FaceTerminal => 'Face Recognition Terminal',
            self::AlarmSiren => 'Alarm Siren',
            self::SmartLight => 'Smart Light',
        };
    }

    /**
     * Placeholder sensor reading shown on the details page
     * until real hardware telemetry is integrated.
     *
     * @return array{label: string, value: string}
     */
    public function sampleReading(): array
    {
        return match ($this) {
            self::MotionSensor => ['label' => 'Motion Detected', 'value' => 'No motion — 12 min ago'],
            self::DoorSensor => ['label' => 'Door State', 'value' => 'Closed'],
            self::WindowSensor => ['label' => 'Window State', 'value' => 'Closed'],
            self::SmokeDetector => ['label' => 'Smoke Level', 'value' => '0.02% obs/m — Normal'],
            self::GasSensor => ['label' => 'Gas Level', 'value' => '3 ppm — Normal'],
            self::WaterLeakSensor => ['label' => 'Leak Status', 'value' => 'Dry'],
            self::TemperatureSensor => ['label' => 'Current Temperature', 'value' => '22.4 °C'],
            self::HumiditySensor => ['label' => 'Humidity', 'value' => '47%'],
            self::SmartLock => ['label' => 'Lock State', 'value' => 'Locked'],
            self::RfidReader => ['label' => 'Last Badge Read', 'value' => 'Badge #2231 — 08:52'],
            self::FingerprintScanner => ['label' => 'Last Scan', 'value' => 'Match — 09:14'],
            self::FaceTerminal => ['label' => 'Last Recognition', 'value' => 'S. Mitchell — 09:02'],
            self::AlarmSiren => ['label' => 'Siren State', 'value' => 'Idle'],
            self::SmartLight => ['label' => 'Light State', 'value' => 'Off — Auto mode'],
        };
    }
}
