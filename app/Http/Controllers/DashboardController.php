<?php

namespace App\Http\Controllers;

use App\Enums\CameraStatus;
use App\Enums\DeviceStatus;
use App\Enums\SignalStrength;
use App\Enums\UserStatus;
use App\Models\AccessEvent;
use App\Models\Camera;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the security dashboard.
     */
    public function index(): View
    {
        // The aggregates only change when hardware state or history
        // changes — a 30-second cache absorbs rapid page switching
        // without ever showing stale-feeling data.
        [$weeklyAccess, $alerts, $cameras, $stats] = \Illuminate\Support\Facades\Cache::remember(
            'dashboard.aggregates',
            30,
            function () {
                $weeklyAccess = $this->weeklyAccessEvents();
                $alerts = $this->activeAlerts();
                $cameras = $this->cameraStatus();

                return [$weeklyAccess, $alerts, $cameras, $this->stats($alerts->count(), $cameras)];
            }
        );

        return view('dashboard', [
            'stats' => $stats,
            'alerts' => $alerts->take(8),
            'alertTotal' => $alerts->count(),
            'accessEvents' => AccessEvent::access()->with('door')->orderByDesc('happened_at')->limit(6)->get(),
            'weeklyAccess' => $weeklyAccess,
            'maxWeekly' => max(max(array_column($weeklyAccess, 'count')), 1),
            'cameras' => $cameras,
        ]);
    }

    /**
     * Headline statistics for the stat cards.
     *
     * @return array<int, array{label: string, value: string|int, meta: string}>
     */
    private function stats(int $alertCount, array $cameras): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', UserStatus::Active)->count();

        $totalDevices = Device::count();
        $onlineDevices = Device::online()->count();

        $recording = Camera::where('recording_enabled', true)->count();
        $lowBattery = Device::lowBattery()->count();

        return [
            ['label' => 'Total Users', 'value' => $totalUsers, 'meta' => $activeUsers.' active'],
            ['label' => 'Cameras Online', 'value' => $cameras['online'].' / '.$cameras['total'], 'meta' => $cameras['meta']],
            ['label' => 'Devices Online', 'value' => $onlineDevices.' / '.$totalDevices, 'meta' => $totalDevices > 0 ? ($totalDevices - $onlineDevices).' offline or in maintenance' : 'No devices registered yet'],
            ['label' => 'Cameras Recording', 'value' => $recording.' / '.$cameras['total'], 'meta' => 'Recording enabled'],
            ['label' => 'Low Battery Devices', 'value' => $lowBattery, 'meta' => 'At or below '.Device::LOW_BATTERY.'%'],
            ['label' => 'Active Alerts', 'value' => $alertCount, 'meta' => $alertCount > 0 ? 'Needs attention' : 'All clear'],
        ];
    }

    /**
     * Camera availability — single source of truth for the stat card
     * and the donut chart.
     *
     * @return array{online: int, total: int, percent: int, meta: string}
     */
    private function cameraStatus(): array
    {
        $total = Camera::count();
        $online = Camera::online()->count();

        return [
            'online' => $online,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($online / $total * 100) : 100,
            'meta' => $total > 0 ? ($total - $online).' offline or in maintenance' : 'No cameras registered yet',
        ];
    }

    /**
     * Live issues derived from equipment state: cameras that are not
     * online, and devices that are offline, low on battery or on a
     * weak signal (mirrors Device::activeAlerts()).
     *
     * @return Collection<int, array{severity: string, label: string, name: string, location: string, url: string}>
     */
    private function activeAlerts(): Collection
    {
        $alerts = collect();

        Camera::whereNot('status', CameraStatus::Online)
            ->latest('updated_at')
            ->get()
            ->each(function (Camera $camera) use ($alerts) {
                $offline = $camera->status === CameraStatus::Offline;
                $alerts->push([
                    'severity' => $offline ? 'danger' : 'warning',
                    'label' => $offline ? 'Camera Offline' : 'Camera In Maintenance',
                    'name' => $camera->name,
                    'location' => $camera->placement(),
                    'url' => route('cameras.show', $camera),
                ]);
            });

        Device::where(function ($query) {
            $query->where('status', DeviceStatus::Offline)
                ->orWhere(fn ($q) => $q->whereNotNull('battery_level')->where('battery_level', '<=', Device::LOW_BATTERY))
                ->orWhere('signal_strength', SignalStrength::Weak);
        })
            ->latest('updated_at')
            ->get()
            ->each(function (Device $device) use ($alerts) {
                foreach ($device->activeAlerts() as $label) {
                    $alerts->push([
                        'severity' => $label === 'Signal Weak' ? 'warning' : 'danger',
                        'label' => $label,
                        'name' => $device->name,
                        'location' => $device->placement(),
                        'url' => route('devices.show', $device),
                    ]);
                }
            });

        return $alerts
            ->sortBy(fn (array $alert) => $alert['severity'] === 'danger' ? 0 : 1)
            ->values();
    }

    /**
     * Access events per day over the last seven days, for the
     * bar chart.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function weeklyAccessEvents(): array
    {
        $since = now()->subDays(6)->startOfDay();

        $countsByDate = AccessEvent::access()
            ->where('happened_at', '>=', $since)
            ->pluck('happened_at')
            ->countBy(fn ($at) => $at->format('Y-m-d'));

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($countsByDate) {
                $day = now()->subDays($daysAgo);

                return [
                    'day' => $day->format('D'),
                    'count' => $countsByDate->get($day->format('Y-m-d'), 0),
                ];
            })
            ->all();
    }
}
