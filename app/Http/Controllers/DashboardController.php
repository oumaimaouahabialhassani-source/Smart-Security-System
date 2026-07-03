<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the security dashboard.
     *
     * Stats are placeholder values until the employees, visitors,
     * cameras and access-log modules provide real data sources.
     */
    public function index(): View
    {
        $stats = [
            ['label' => 'Total Employees', 'value' => 248, 'meta' => '+4 this month', 'icon' => 'users'],
            ['label' => 'Active Employees', 'value' => 231, 'meta' => '93% on site', 'icon' => 'user-check'],
            ['label' => 'Visitors Today', 'value' => 37, 'meta' => '12 currently inside', 'icon' => 'badge'],
            ['label' => 'Cameras Online', 'value' => '46 / 48', 'meta' => '2 offline — Zone C', 'icon' => 'camera'],
            ['label' => 'Doors Secured', 'value' => '18 / 18', 'meta' => 'All zones locked', 'icon' => 'lock'],
            ['label' => 'Alerts (24h)', 'value' => 3, 'meta' => '1 unresolved', 'icon' => 'alert'],
        ];

        $accessEvents = [
            ['time' => '09:42', 'name' => 'Sarah Mitchell', 'type' => 'Employee', 'door' => 'Main Entrance', 'status' => 'Granted'],
            ['time' => '09:38', 'name' => 'David Okoro', 'type' => 'Visitor', 'door' => 'Reception Gate', 'status' => 'Granted'],
            ['time' => '09:31', 'name' => 'Unknown Badge #4412', 'type' => '—', 'door' => 'Server Room', 'status' => 'Denied'],
            ['time' => '09:24', 'name' => 'Amira Hassan', 'type' => 'Employee', 'door' => 'Lab B', 'status' => 'Granted'],
            ['time' => '09:15', 'name' => 'James Carter', 'type' => 'Contractor', 'door' => 'Loading Dock', 'status' => 'Granted'],
            ['time' => '09:02', 'name' => 'Unknown Badge #4412', 'type' => '—', 'door' => 'Server Room', 'status' => 'Denied'],
        ];

        // Access events per day for the placeholder bar chart (Mon..Sun).
        $weeklyAccess = [
            ['day' => 'Mon', 'count' => 320],
            ['day' => 'Tue', 'count' => 410],
            ['day' => 'Wed', 'count' => 385],
            ['day' => 'Thu', 'count' => 460],
            ['day' => 'Fri', 'count' => 300],
            ['day' => 'Sat', 'count' => 120],
            ['day' => 'Sun', 'count' => 85],
        ];

        return view('dashboard', [
            'stats' => $stats,
            'accessEvents' => $accessEvents,
            'weeklyAccess' => $weeklyAccess,
            'maxWeekly' => max(array_column($weeklyAccess, 'count')),
        ]);
    }
}
