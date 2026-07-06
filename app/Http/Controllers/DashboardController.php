<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the security dashboard.
     */
    public function index(): View
    {
        $weeklyAccess = $this->weeklyAccessCounts();

        return view('dashboard', [
            'stats' => $this->stats(),
            'accessEvents' => $this->recentAccessEvents(),
            'weeklyAccess' => $weeklyAccess,
            'maxWeekly' => max(array_column($weeklyAccess, 'count')),
            'cameras' => $this->cameraStatus(),
        ]);
    }

    /**
     * Headline statistics for the stat cards.
     *
     * Placeholder values — swap each entry for a real query as the
     * matching module lands (e.g. Employee::count(), Visitor::today()->count()).
     *
     * @return array<int, array{label: string, value: string|int, meta: string}>
     */
    private function stats(): array
    {
        $cameras = $this->cameraStatus();

        return [
            ['label' => 'Total Employees', 'value' => 248, 'meta' => '+4 this month'],
            ['label' => 'Active Employees', 'value' => 231, 'meta' => '93% on site'],
            ['label' => 'Visitors Today', 'value' => 37, 'meta' => '12 currently inside'],
            ['label' => 'Cameras Online', 'value' => $cameras['online'].' / '.$cameras['total'], 'meta' => $cameras['meta']],
            ['label' => 'Doors Secured', 'value' => '18 / 18', 'meta' => 'All zones locked'],
            ['label' => 'Alerts (24h)', 'value' => 3, 'meta' => '1 unresolved'],
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
     * Latest entries for the activity table.
     * Replace with AccessLog::latest()->limit(6)->get() when available.
     *
     * @return array<int, array{time: string, name: string, type: string, door: string, status: string}>
     */
    private function recentAccessEvents(): array
    {
        return [
            ['time' => '09:42', 'name' => 'Sarah Mitchell', 'type' => 'Employee', 'door' => 'Main Entrance', 'status' => 'Granted'],
            ['time' => '09:38', 'name' => 'David Okoro', 'type' => 'Visitor', 'door' => 'Reception Gate', 'status' => 'Granted'],
            ['time' => '09:31', 'name' => 'Unknown Badge #4412', 'type' => '—', 'door' => 'Server Room', 'status' => 'Denied'],
            ['time' => '09:24', 'name' => 'Amira Hassan', 'type' => 'Employee', 'door' => 'Lab B', 'status' => 'Granted'],
            ['time' => '09:15', 'name' => 'James Carter', 'type' => 'Contractor', 'door' => 'Loading Dock', 'status' => 'Granted'],
            ['time' => '09:02', 'name' => 'Unknown Badge #4412', 'type' => '—', 'door' => 'Server Room', 'status' => 'Denied'],
        ];
    }

    /**
     * Access events per day (Mon..Sun) for the bar chart.
     * Replace with an AccessLog groupBy(day) aggregate when available.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function weeklyAccessCounts(): array
    {
        return [
            ['day' => 'Mon', 'count' => 320],
            ['day' => 'Tue', 'count' => 410],
            ['day' => 'Wed', 'count' => 385],
            ['day' => 'Thu', 'count' => 460],
            ['day' => 'Fri', 'count' => 300],
            ['day' => 'Sat', 'count' => 120],
            ['day' => 'Sun', 'count' => 85],
        ];
    }
}
