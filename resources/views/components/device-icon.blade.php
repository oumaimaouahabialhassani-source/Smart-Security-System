@props(['type'])

@php
    $path = match ($type) {
        App\Enums\DeviceType::MotionSensor => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
        App\Enums\DeviceType::DoorSensor => 'M18 20V6a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v14M2 20h20M14 12v.01',
        App\Enums\DeviceType::WindowSensor => 'M3 3h18v18H3zM3 12h18M12 3v18',
        App\Enums\DeviceType::SmokeDetector => 'M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z',
        App\Enums\DeviceType::GasSensor => 'M12 2a4 4 0 0 1 4 4c0 1.95-1.4 3.58-3.25 3.93L12 22l-.75-12.07A4.001 4.001 0 0 1 12 2z',
        App\Enums\DeviceType::WaterLeakSensor => 'M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z',
        App\Enums\DeviceType::TemperatureSensor => 'M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z',
        App\Enums\DeviceType::HumiditySensor => 'M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7zM8 15a4 4 0 0 0 4 4',
        App\Enums\DeviceType::SmartLock => 'M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2zM7 11V7a5 5 0 0 1 10 0v4',
        App\Enums\DeviceType::RfidReader => 'M2 9.5a10 10 0 0 1 20 0M5 12.5a7 7 0 0 1 14 0M8.5 15.5a3.5 3.5 0 0 1 7 0M12 19h.01',
        App\Enums\DeviceType::FingerprintScanner => 'M12 11a3 3 0 0 0-3 3c0 2.5-.5 4.5-1.5 6M12 7a7 7 0 0 0-7 7c0 1.5-.2 3-.6 4.3M12 3a11 11 0 0 1 11 11c0 2.5-.3 5-.8 7M15 14a19 19 0 0 1-1 6.5M18.5 13.5c.2 2.7-.1 5.4-.8 8',
        App\Enums\DeviceType::FaceTerminal => 'M9 10h.01M15 10h.01M9.5 15a3.5 3.5 0 0 0 5 0M4 8V6a2 2 0 0 1 2-2h2M4 16v2a2 2 0 0 0 2 2h2M16 4h2a2 2 0 0 1 2 2v2M16 20h2a2 2 0 0 0 2-2v-2',
        App\Enums\DeviceType::AlarmSiren => 'M12 3a7 7 0 0 1 7 7v8H5v-8a7 7 0 0 1 7-7zM3 21h18M12 3V1M4.2 5.2L3 4M19.8 5.2L21 4',
        App\Enums\DeviceType::SmartLight => 'M9 18h6M10 22h4M12 2a7 7 0 0 1 4.9 12 4.5 4.5 0 0 0-1.4 3H8.5a4.5 4.5 0 0 0-1.4-3A7 7 0 0 1 12 2z',
    };
@endphp

<span {{ $attributes->merge(['class' => 'cam-thumb']) }} aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
</span>
