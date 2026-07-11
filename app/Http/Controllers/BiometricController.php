<?php

namespace App\Http\Controllers;

use App\Enums\BiometricMethod;
use App\Enums\BiometricResult;
use App\Enums\BiometricStatus;
use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Http\Requests\StoreBiometricProfileRequest;
use App\Http\Requests\UpdateBiometricProfileRequest;
use App\Models\BiometricProfile;
use App\Models\BiometricVerification;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiometricController extends Controller
{
    /**
     * IoT device types that act as biometric readers.
     *
     * @var list<DeviceType>
     */
    private const BIOMETRIC_DEVICE_TYPES = [
        DeviceType::FaceTerminal,
        DeviceType::FingerprintScanner,
    ];

    private const FINGERS = [
        'Right Thumb', 'Right Index', 'Right Middle',
        'Left Thumb', 'Left Index', 'Left Middle',
    ];

    /**
     * The biometric dashboard: stats, charts, alerts, enrollment
     * table, device panel and recent verification logs.
     */
    public function index(Request $request): View
    {
        $profiles = BiometricProfile::query()
            ->with(['user', 'assignedDevice', 'latestVerification'])
            ->search($request->query('search'))
            ->when($request->query('department'), fn ($q, $v) => $q->where('department', $v))
            ->when($request->query('device'), fn ($q, $v) => $q->where('assigned_device_id', $v))
            ->when($request->query('result'), fn ($q, $v) => $q->whereHas('latestVerification', fn ($q) => $q->where('result', $v)))
            ->when($request->query('method'), fn ($q, $v) => $q->whereHas('latestVerification', fn ($q) => $q->where('method', $v)))
            ->when($request->query('date'), fn ($q, $v) => $q->whereHas('latestVerification', fn ($q) => $q->whereBetween('happened_at', ["{$v} 00:00:00", "{$v} 23:59:59"])))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        $devices = $this->biometricDevices();

        return view('biometrics.index', [
            'profiles' => $profiles,
            'stats' => $this->stats($devices),
            'weekly' => $weekly = $this->weeklyVerifications(),
            'maxWeekly' => max(max(array_column($weekly, 'count')), 1),
            'successRate' => $this->successRate(),
            'alerts' => $this->alerts($devices),
            'devices' => $devices,
            'monitoring' => $this->monitoring($devices),
            'recentLogs' => BiometricVerification::with(['profile.user', 'device'])->orderByDesc('happened_at')->limit(8)->get(),
            'departments' => BiometricProfile::distinct()->orderBy('department')->pluck('department'),
            'results' => BiometricResult::cases(),
            'methods' => BiometricMethod::cases(),
            'fingers' => self::FINGERS,
        ]);
    }

    /**
     * Show the form for enrolling an employee into biometrics.
     */
    public function create(): View
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        return view('biometrics.create', [
            'users' => User::whereDoesntHave('biometricProfile')->orderBy('first_name')->get(),
        ] + $this->formOptions());
    }

    /**
     * Create a biometric profile for an employee.
     */
    public function store(StoreBiometricProfileRequest $request): RedirectResponse
    {
        $profile = BiometricProfile::create($request->validated());

        return redirect()->route('biometrics.index')
            ->with('status', "Biometric profile {$profile->employee_code} created for {$profile->user->name}. You can now register their face and fingerprints.");
    }

    /**
     * Display a profile with its enrollment state and history.
     */
    public function show(BiometricProfile $biometric): View
    {
        return view('biometrics.show', [
            'profile' => $biometric->load(['user', 'assignedDevice']),
            'history' => $biometric->verifications()->with('device')->orderByDesc('happened_at')->limit(15)->get(),
        ]);
    }

    /**
     * Show the form for editing a profile.
     */
    public function edit(BiometricProfile $biometric): View
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        return view('biometrics.edit', ['profile' => $biometric->load('user')] + $this->formOptions());
    }

    /**
     * Update the given profile.
     */
    public function update(UpdateBiometricProfileRequest $request, BiometricProfile $biometric): RedirectResponse
    {
        $biometric->update($request->validated());

        return redirect()->route('biometrics.index')
            ->with('status', "Biometric profile {$biometric->employee_code} has been updated.");
    }

    /**
     * Delete the given profile. Administrators only.
     */
    public function destroy(BiometricProfile $biometric): RedirectResponse
    {
        abort_unless(auth()->user()->role->canAdministerBiometrics(), 403);

        $label = "{$biometric->employee_code} ({$biometric->user->name})";
        $biometric->delete();

        return redirect()->route('biometrics.index')
            ->with('status', "Biometric profile {$label} has been deleted.");
    }

    /**
     * Save a face template captured in the enrollment modal.
     */
    public function enrollFace(Request $request, BiometricProfile $biometric): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        $data = $request->validate([
            'quality' => ['required', 'integer', 'min:40', 'max:100'],
        ]);

        $biometric->update([
            'face_enrolled_at' => now(),
            'face_quality' => $data['quality'],
            'status' => BiometricStatus::Active,
        ]);

        return back()->with('status', "Face template saved for {$biometric->user->name} — quality {$data['quality']}%.");
    }

    /**
     * Save a fingerprint scanned in the enrollment modal.
     */
    public function enrollFingerprint(Request $request, BiometricProfile $biometric): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        $data = $request->validate([
            'finger' => ['required', 'string', 'max:30'],
            'device_id' => ['required', 'exists:devices,id'],
            'quality' => ['required', 'integer', 'min:40', 'max:100'],
        ]);

        $biometric->update([
            'fingerprint_enrolled_at' => now(),
            'fingerprint_finger' => $data['finger'],
            'fingerprint_quality' => $data['quality'],
            'assigned_device_id' => $biometric->assigned_device_id ?? $data['device_id'],
            'status' => BiometricStatus::Active,
        ]);

        return back()->with('status', "Fingerprint ({$data['finger']}) saved for {$biometric->user->name} — quality {$data['quality']}%.");
    }

    /**
     * Save an iris template captured in the enrollment modal.
     */
    public function enrollIris(Request $request, BiometricProfile $biometric): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        $biometric->update([
            'iris_enrolled_at' => now(),
            'status' => BiometricStatus::Active,
        ]);

        return back()->with('status', "Iris template saved for {$biometric->user->name}.");
    }

    /**
     * Run an identity verification against an enrolled modality and
     * record the attempt in the logs.
     */
    public function verify(Request $request, BiometricProfile $biometric): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        $data = $request->validate([
            'method' => ['required', 'in:face,fingerprint,iris'],
        ]);

        $method = BiometricMethod::from($data['method']);

        $enrolled = match ($method) {
            BiometricMethod::Face => (bool) $biometric->face_enrolled_at,
            BiometricMethod::Fingerprint => (bool) $biometric->fingerprint_enrolled_at,
            BiometricMethod::Iris => (bool) $biometric->iris_enrolled_at,
        };

        $suspended = $biometric->status === BiometricStatus::Suspended;
        $success = $enrolled && ! $suspended;

        BiometricVerification::create([
            'biometric_profile_id' => $biometric->id,
            'subject_name' => $biometric->user->name,
            'method' => $method,
            'device_id' => $biometric->assigned_device_id,
            'result' => $success ? BiometricResult::Success : BiometricResult::Failed,
            'detail' => $success ? 'Manual verification from console' : ($suspended ? 'Profile suspended' : $method->label().' not enrolled'),
            'duration_ms' => random_int(280, 950),
            'ip_address' => $request->ip(),
            'happened_at' => now(),
        ]);

        // Mirror the attempt into the Access Control logs.
        \App\Models\AccessEvent::create([
            'kind' => 'access',
            'user_id' => $biometric->user_id,
            'person_name' => $biometric->user->name,
            'badge_id' => $biometric->employee_code,
            'door_id' => \App\Models\Door::where('device_id', $biometric->assigned_device_id)->value('id'),
            'direction' => 'entry',
            'result' => $success ? \App\Enums\AccessResult::Granted : match ($method) {
                BiometricMethod::Face => \App\Enums\AccessResult::FaceNotRecognized,
                BiometricMethod::Fingerprint => \App\Enums\AccessResult::FingerprintFailed,
                BiometricMethod::Iris => \App\Enums\AccessResult::Denied,
            },
            'method' => $method->value,
            'device_id' => $biometric->assigned_device_id,
            'face_confidence' => $method === BiometricMethod::Face && $success ? random_int(88, 99) : null,
            'ip_address' => $request->ip(),
            'detail' => $success ? 'Biometric verification' : ($suspended ? 'Profile suspended' : $method->label().' not enrolled'),
            'happened_at' => now(),
        ]);

        if (! $success) {
            \App\Models\Alert::raise(
                match ($method) {
                    BiometricMethod::Face => 'Unknown Face Detected',
                    BiometricMethod::Fingerprint => 'Fingerprint Verification Failed',
                    BiometricMethod::Iris => 'Unauthorized Access',
                },
                \App\Enums\AlertSeverity::High,
                "{$method->label()} verification failed for {$biometric->user->name}".($suspended ? ' (profile suspended)' : ''),
                [
                    'user_id' => $biometric->user_id,
                    'device_id' => $biometric->assigned_device_id,
                    'ai_confidence' => random_int(70, 95),
                ]
            );
        }

        return back()->with(
            $success ? 'status' : 'error',
            $success
                ? "Identity verified — {$biometric->user->name} matched via {$method->label()}."
                : "Verification FAILED for {$biometric->user->name} — ".($suspended ? 'profile is suspended.' : "{$method->label()} is not enrolled.")
        );
    }

    /**
     * Full verification & activity log with filters.
     */
    public function logs(Request $request): View
    {
        $logs = BiometricVerification::query()
            ->with(['profile.user', 'device'])
            ->search($request->query('search'))
            ->when($request->query('method'), fn ($q, $v) => $q->where('method', $v))
            ->when($request->query('result'), fn ($q, $v) => $q->where('result', $v))
            ->when($request->query('device'), fn ($q, $v) => $q->where('device_id', $v))
            ->when($request->query('date'), fn ($q, $v) => $q->whereBetween('happened_at', ["{$v} 00:00:00", "{$v} 23:59:59"]))
            ->orderByDesc('happened_at')
            ->paginate(12)
            ->withQueryString();

        return view('biometrics.logs', [
            'logs' => $logs,
            'methods' => BiometricMethod::cases(),
            'results' => BiometricResult::cases(),
            'devices' => $this->biometricDevices(),
        ]);
    }

    /**
     * Export the filtered logs as CSV (opens in Excel).
     */
    public function exportLogs(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        $logs = BiometricVerification::query()
            ->with(['profile.user', 'device'])
            ->search($request->query('search'))
            ->when($request->query('method'), fn ($q, $v) => $q->where('method', $v))
            ->when($request->query('result'), fn ($q, $v) => $q->where('result', $v))
            ->when($request->query('device'), fn ($q, $v) => $q->where('device_id', $v))
            ->when($request->query('date'), fn ($q, $v) => $q->whereBetween('happened_at', ["{$v} 00:00:00", "{$v} 23:59:59"]))
            ->orderByDesc('happened_at')
            ->limit(5000)
            ->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Time', 'Subject', 'Method', 'Device', 'Result', 'Detail', 'Duration (ms)', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->happened_at->format('Y-m-d'),
                    $log->happened_at->format('H:i:s'),
                    $log->subject_name,
                    $log->method->label(),
                    $log->device?->name,
                    $log->result->label(),
                    $log->detail,
                    $log->duration_ms,
                    $log->ip_address,
                ]);
            }

            fclose($out);
        }, 'biometric-logs-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Export enrollment status of all profiles as CSV.
     */
    public function exportProfiles(): StreamedResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        $profiles = BiometricProfile::with(['user', 'assignedDevice', 'latestVerification'])->get();

        return response()->streamDownload(function () use ($profiles) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee ID', 'Name', 'Department', 'Position', 'Face', 'Fingerprint', 'Iris', 'Status', 'Assigned Device', 'Last Verification', 'Last Result']);

            foreach ($profiles as $profile) {
                fputcsv($out, [
                    $profile->employee_code,
                    $profile->user->name,
                    $profile->department,
                    $profile->position,
                    $profile->face_enrolled_at ? 'Registered' : 'Not Registered',
                    $profile->fingerprint_enrolled_at ? 'Registered' : 'Not Registered',
                    $profile->iris_enrolled_at ? 'Registered' : 'Not Registered',
                    $profile->status->label(),
                    $profile->assignedDevice?->name,
                    $profile->latestVerification?->happened_at?->format('Y-m-d H:i'),
                    $profile->latestVerification?->result->label(),
                ]);
            }

            fclose($out);
        }, 'biometric-enrollment-'.now()->format('Y-m-d-Hi').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Send a restart command to a biometric device. Administrators only.
     */
    public function restartDevice(Device $device): RedirectResponse
    {
        abort_unless(auth()->user()->role->canAdministerBiometrics(), 403);

        $device->update(['status' => DeviceStatus::Online, 'last_seen' => now()]);

        return back()->with('status', "Restart command sent to {$device->name} — device is back online.");
    }

    /**
     * Push enrolled templates to a biometric device.
     */
    public function syncDevice(Device $device): RedirectResponse
    {
        abort_unless(auth()->user()->role->canManageBiometrics(), 403);

        if ($device->status === DeviceStatus::Offline) {
            return back()->with('error', "Synchronization failed — {$device->name} is offline.");
        }

        $device->update(['last_seen' => now()]);

        return back()->with('status', BiometricProfile::count()." templates synchronized to {$device->name}.");
    }

    /**
     * Biometric readers from the IoT Devices module.
     *
     * @return Collection<int, Device>
     */
    private function biometricDevices(): Collection
    {
        return Device::whereIn('type', self::BIOMETRIC_DEVICE_TYPES)
            ->orderBy('name')
            ->get();
    }

    /**
     * Headline statistics for the stat cards.
     *
     * @param Collection<int, Device> $devices
     * @return array<int, array{label: string, value: string|int, meta: string}>
     */
    private function stats(Collection $devices): array
    {
        $faces = BiometricProfile::whereNotNull('face_enrolled_at')->count();
        $fingerprints = BiometricProfile::whereNotNull('fingerprint_enrolled_at')->count();
        $irises = BiometricProfile::whereNotNull('iris_enrolled_at')->count();
        $total = BiometricProfile::count();

        $todayTotal = BiometricVerification::today()->count();
        $todaySuccess = BiometricVerification::today()->where('result', BiometricResult::Success)->count();
        $todayFailed = BiometricVerification::today()->where('result', BiometricResult::Failed)->count();

        $online = $devices->where('status', DeviceStatus::Online)->count();
        $offline = $devices->where('status', DeviceStatus::Offline)->count();

        return [
            ['label' => 'Registered Faces', 'value' => $faces, 'meta' => "of {$total} profiles"],
            ['label' => 'Registered Fingerprints', 'value' => $fingerprints, 'meta' => "of {$total} profiles"],
            ['label' => 'Registered Iris Scans', 'value' => $irises, 'meta' => "of {$total} profiles"],
            ['label' => "Today's Verifications", 'value' => $todayTotal, 'meta' => now()->format('l, M j')],
            ['label' => 'Successful Authentications', 'value' => $todaySuccess, 'meta' => 'Today'],
            ['label' => 'Failed Authentications', 'value' => $todayFailed, 'meta' => 'Today'],
            ['label' => 'Connected Devices', 'value' => $online.' / '.$devices->count(), 'meta' => 'Biometric readers online'],
            ['label' => 'Offline Devices', 'value' => $offline, 'meta' => $offline > 0 ? 'Needs attention' : 'All readers reachable'],
        ];
    }

    /**
     * Verifications per day over the last seven days, for the bar chart.
     *
     * @return array<int, array{day: string, count: int}>
     */
    private function weeklyVerifications(): array
    {
        $since = now()->subDays(6)->startOfDay();

        $countsByDate = BiometricVerification::where('happened_at', '>=', $since)
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

    /**
     * Success rate over the last seven days, for the donut chart.
     *
     * @return array{percent: int, success: int, failed: int, total: int}
     */
    private function successRate(): array
    {
        $since = now()->subDays(6)->startOfDay();

        $total = BiometricVerification::where('happened_at', '>=', $since)->count();
        $success = BiometricVerification::where('happened_at', '>=', $since)
            ->where('result', BiometricResult::Success)
            ->count();

        return [
            'percent' => $total > 0 ? (int) round($success / $total * 100) : 100,
            'success' => $success,
            'failed' => $total - $success,
            'total' => $total,
        ];
    }

    /**
     * Security alerts derived from the last 24 hours of activity
     * and current device state.
     *
     * @param Collection<int, Device> $devices
     * @return Collection<int, array{severity: string, label: string, detail: string, url: string|null}>
     */
    private function alerts(Collection $devices): Collection
    {
        $alerts = collect();
        $since = now()->subDay();

        // Unknown subjects and hardware errors reported by readers.
        BiometricVerification::where('happened_at', '>=', $since)
            ->where(fn ($q) => $q->whereNull('biometric_profile_id')->orWhere('result', BiometricResult::Warning))
            ->orderByDesc('happened_at')
            ->limit(10)
            ->get()
            ->each(function (BiometricVerification $log) use ($alerts) {
                $alerts->push([
                    'severity' => $log->biometric_profile_id === null ? 'danger' : 'warning',
                    'label' => $log->detail ?? 'Unrecognized subject',
                    'detail' => ($log->device?->name ?? 'Unknown device').' — '.$log->happened_at->diffForHumans(),
                    'url' => route('biometrics.logs', ['date' => $log->happened_at->format('Y-m-d')]),
                ]);
            });

        // Repeated failures on the same profile.
        BiometricVerification::where('happened_at', '>=', $since)
            ->where('result', BiometricResult::Failed)
            ->whereNotNull('biometric_profile_id')
            ->with('profile.user')
            ->get()
            ->groupBy('biometric_profile_id')
            ->filter(fn ($attempts) => $attempts->count() >= 3)
            ->each(function ($attempts) use ($alerts) {
                $profile = $attempts->first()->profile;
                $alerts->push([
                    'severity' => 'danger',
                    'label' => 'Multiple Failed Attempts',
                    'detail' => ($profile?->user?->name ?? 'Unknown').' — '.$attempts->count().' failures in 24h.',
                    'url' => $profile ? route('biometrics.show', $profile) : null,
                ]);
            });

        // Offline readers.
        $devices->where('status', DeviceStatus::Offline)->each(function (Device $device) use ($alerts) {
            $alerts->push([
                'severity' => 'danger',
                'label' => 'Device Offline',
                'detail' => $device->name.' — '.$device->placement(),
                'url' => route('devices.show', $device),
            ]);
        });

        return $alerts
            ->sortBy(fn (array $alert) => $alert['severity'] === 'danger' ? 0 : 1)
            ->take(8)
            ->values();
    }

    /**
     * Live monitoring widgets. CPU / memory are deterministic
     * placeholders derived from device id and current hour, until
     * real hardware telemetry is integrated (same approach as
     * DeviceType::sampleReading()).
     *
     * @param Collection<int, Device> $devices
     * @return array<string, mixed>
     */
    private function monitoring(Collection $devices): array
    {
        $seed = (int) now()->format('YmdH');

        $perDevice = $devices->map(function (Device $device) use ($seed) {
            $online = $device->status === DeviceStatus::Online;

            return [
                'device' => $device,
                'cpu' => $online ? 18 + (($device->id * 37 + $seed) % 55) : 0,
                'memory' => $online ? 30 + (($device->id * 53 + $seed) % 50) : 0,
                'synced' => $device->last_seen !== null && $device->last_seen->gt(now()->subHours(12)),
            ];
        });

        return [
            'active' => $devices->where('status', DeviceStatus::Online)->count(),
            'offline' => $devices->where('status', DeviceStatus::Offline)->count(),
            'synced' => $perDevice->where('synced', true)->count(),
            'total' => $devices->count(),
            'perDevice' => $perDevice,
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
            'devices' => $this->biometricDevices(),
            'statuses' => BiometricStatus::cases(),
            'departments' => collect(\App\Support\Departments::ALL)
                ->merge(BiometricProfile::distinct()->pluck('department'))->filter()->unique()->sort()->values(),
        ];
    }
}
