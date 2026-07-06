<?php

namespace App\Http\Controllers;

use App\Enums\DeviceProtocol;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\SignalStrength;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    /**
     * List devices with summary stats, search and filters.
     */
    public function index(Request $request): View
    {
        $devices = Device::query()
            ->search($request->query('search'))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('building'), fn ($q, $v) => $q->where('building', $v))
            ->when($request->query('floor'), fn ($q, $v) => $q->where('floor', $v))
            ->when($request->query('zone'), fn ($q, $v) => $q->where('zone', $v))
            ->when($request->query('signal'), fn ($q, $v) => $q->where('signal_strength', $v))
            ->when($request->query('battery'), function ($q, $v) {
                match ($v) {
                    'low' => $q->lowBattery(),
                    'medium' => $q->whereBetween('battery_level', [Device::LOW_BATTERY + 1, 60]),
                    'high' => $q->where('battery_level', '>', 60),
                    'mains' => $q->whereNull('battery_level'),
                    default => null,
                };
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('devices.index', [
            'devices' => $devices,
            'stats' => $this->stats(),
            'types' => DeviceType::cases(),
            'statuses' => DeviceStatus::cases(),
            'signals' => SignalStrength::cases(),
            'buildings' => Device::distinct()->orderBy('building')->pluck('building'),
            'floors' => Device::distinct()->orderBy('floor')->pluck('floor'),
            'zones' => Device::distinct()->orderBy('zone')->pluck('zone'),
        ]);
    }

    /**
     * Show the form for registering a new device.
     */
    public function create(): View
    {
        return view('devices.create', $this->formOptions());
    }

    /**
     * Store a newly registered device.
     */
    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $device = Device::create($request->validated());

        return redirect()->route('devices.index')
            ->with('status', "Device {$device->name} ({$device->device_id}) has been registered.");
    }

    /**
     * Display a device's details, health, sensors and logs.
     */
    public function show(Device $device): View
    {
        return view('devices.show', ['device' => $device]);
    }

    /**
     * Show the form for editing a device.
     */
    public function edit(Device $device): View
    {
        return view('devices.edit', ['device' => $device] + $this->formOptions());
    }

    /**
     * Update the given device.
     */
    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $device->update($data);

        return redirect()->route('devices.index')
            ->with('status', "Device {$device->name} has been updated.");
    }

    /**
     * Delete the given device.
     */
    public function destroy(Device $device): RedirectResponse
    {
        $name = $device->name;
        $device->delete();

        return redirect()->route('devices.index')
            ->with('status', "Device {$name} has been deleted.");
    }

    /**
     * Summary counts for the stat cards.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        $offline = Device::where('status', DeviceStatus::Offline)->count();
        $lowBattery = Device::lowBattery()->count();
        $weakSignal = Device::where('signal_strength', SignalStrength::Weak)->count();

        return [
            'total' => Device::count(),
            'online' => Device::online()->count(),
            'offline' => $offline,
            'low_battery' => $lowBattery,
            // Derived placeholder until the Alerts module lands.
            'alerts' => $offline + $lowBattery + $weakSignal,
        ];
    }

    /**
     * Shared select options for the create/edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'types' => DeviceType::cases(),
            'statuses' => DeviceStatus::cases(),
            'signals' => SignalStrength::cases(),
            'protocols' => DeviceProtocol::cases(),
        ];
    }
}
