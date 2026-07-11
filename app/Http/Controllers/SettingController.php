<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingController extends Controller
{
    /**
     * Validation rules per settings group. Also acts as the
     * allow-list of keys a group accepts.
     *
     * @return array<string, array<string, mixed>>
     */
    private function rules(string $group): array
    {
        return match ($group) {
            'general' => [
                'company_name' => ['required', 'string', 'max:100'],
                'company_email' => ['nullable', 'email', 'max:255'],
                'company_phone' => ['nullable', 'string', 'max:30'],
                'company_address' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
                'timezone' => ['required', 'timezone'],
                'language' => ['required', Rule::in(['en', 'fr', 'ar'])],
                'date_format' => ['required', Rule::in(['Y-m-d', 'd/m/Y', 'M j, Y'])],
                'time_format' => ['required', Rule::in(['H:i', 'h:i A'])],
            ],
            'security' => [
                'session_timeout' => ['required', 'integer', 'min:5', 'max:1440'],
                'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
                'password_require_uppercase' => ['nullable', 'boolean'],
                'password_require_numbers' => ['nullable', 'boolean'],
                'password_require_symbols' => ['nullable', 'boolean'],
                'password_expiration_days' => ['required', 'integer', 'min:0', 'max:365'],
                'max_login_attempts' => ['required', 'integer', 'min:3', 'max:20'],
                'lock_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'auto_logout' => ['nullable', 'boolean'],
                'two_factor' => ['nullable', 'boolean'],
            ],
            'notifications' => [
                'email' => ['nullable', 'boolean'],
                'sms' => ['nullable', 'boolean'],
                'push' => ['nullable', 'boolean'],
                'visitor_alerts' => ['nullable', 'boolean'],
                'access_denied_alerts' => ['nullable', 'boolean'],
                'camera_offline_alerts' => ['nullable', 'boolean'],
                'device_offline_alerts' => ['nullable', 'boolean'],
                'critical_alerts' => ['nullable', 'boolean'],
            ],
            'cameras' => [
                'recording_quality' => ['required', Rule::in(['720p', '1080p', '1440p', '4K'])],
                'motion_detection' => ['nullable', 'boolean'],
                'auto_recording' => ['nullable', 'boolean'],
                'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
                'snapshot_quality' => ['required', 'integer', 'min:10', 'max:100'],
                'stream_quality' => ['required', Rule::in(['low', 'medium', 'high'])],
                'max_storage_gb' => ['required', 'integer', 'min:10', 'max:10000'],
            ],
            'biometrics' => [
                'face_threshold' => ['required', 'integer', 'min:50', 'max:100'],
                'fingerprint_threshold' => ['required', 'integer', 'min:50', 'max:100'],
                'anti_spoofing' => ['nullable', 'boolean'],
                'enrollment_quality' => ['required', 'integer', 'min:40', 'max:100'],
                'max_retry_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            ],
            'devices' => [
                'heartbeat_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
                'scan_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'offline_timeout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'auto_reconnect' => ['nullable', 'boolean'],
                'auto_sync' => ['nullable', 'boolean'],
            ],
            'appearance' => [
                'theme' => ['required', Rule::in(['system', 'dark', 'light'])],
                'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'sidebar_style' => ['required', Rule::in(['default', 'compact'])],
                'dashboard_layout' => ['required', Rule::in(['default', 'wide'])],
                'logo' => ['nullable', 'image', 'max:1024'],
                'favicon' => ['nullable', 'image', 'max:256'],
            ],
            'mail' => [
                'driver' => ['required', Rule::in(['smtp', 'log'])],
                'host' => ['nullable', 'string', 'max:255'],
                'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:255'],
                'encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
                'sender_name' => ['nullable', 'string', 'max:100'],
                'sender_email' => ['nullable', 'email', 'max:255'],
            ],
            'backups' => [
                'auto_daily' => ['nullable', 'boolean'],
                'auto_weekly' => ['nullable', 'boolean'],
                'auto_monthly' => ['nullable', 'boolean'],
            ],
            default => abort(404),
        };
    }

    /**
     * The Settings page, all groups in tabs.
     */
    public function index(): View
    {
        $this->authorizeSettings();

        return view('settings.index', [
            'values' => [
                'general' => Setting::group('general'),
                'security' => Setting::group('security'),
                'notifications' => Setting::group('notifications'),
                'cameras' => Setting::group('cameras'),
                'biometrics' => Setting::group('biometrics'),
                'devices' => Setting::group('devices'),
                'appearance' => Setting::group('appearance'),
                'mail' => Setting::group('mail'),
                'backups' => Setting::group('backups'),
            ],
            'timezones' => \DateTimeZone::listIdentifiers(),
            'backupFiles' => $this->backupFiles(),
            'system' => $this->systemInfo(),
        ]);
    }

    /**
     * Persist one settings group.
     */
    public function update(Request $request, string $group): RedirectResponse
    {
        $this->authorizeSettings();

        $rules = $this->rules($group);
        $data = Validator::make($request->all(), $rules)->validate();

        foreach ($rules as $key => $keyRules) {
            // Checkboxes: absent means off.
            if (in_array('boolean', $keyRules, true)) {
                Setting::set("{$group}.{$key}", $request->boolean($key));
                continue;
            }

            // File uploads are stored and saved as their public path.
            if ($key === 'logo' || $key === 'favicon') {
                if ($request->hasFile($key)) {
                    Setting::set("appearance.{$key}", '/storage/'.$request->file($key)->store('branding', 'public'));
                }
                continue;
            }

            // The SMTP password is write-only: blank keeps the current one.
            if ($group === 'mail' && $key === 'password') {
                if (filled($data[$key] ?? null)) {
                    Setting::set('mail.password', encrypt($data[$key]));
                }
                continue;
            }

            if (array_key_exists($key, $data)) {
                Setting::set("{$group}.{$key}", $data[$key]);
            }
        }

        return redirect()->route('settings.index', ['tab' => $group])
            ->with('status', ucfirst($group).' settings saved.');
    }

    /**
     * Send a test email using the stored SMTP configuration.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $this->authorizeSettings();

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').' — your mail configuration works.',
                fn ($message) => $message->to($request->user()->email)->subject('Test email — '.config('app.name')),
            );

            return back()->with('status', 'Test email sent to '.$request->user()->email.' (driver: '.config('mail.default').'). Check the inbox — or storage/logs/laravel.log when using the log driver.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Mail connection failed: '.$e->getMessage());
        }
    }

    /**
     * Create a manual backup now.
     */
    public function backupCreate(): RedirectResponse
    {
        $this->authorizeSettings();

        Artisan::call('backup:run', ['--label' => 'manual']);
        \App\Models\AuditLog::record('Settings', 'Backup Created', 'Manual database backup created');

        return redirect()->route('settings.index', ['tab' => 'backups'])
            ->with('status', trim(Artisan::output()));
    }

    public function backupDownload(string $file): BinaryFileResponse
    {
        $this->authorizeSettings();
        $path = $this->backupPath($file);

        return response()->download($path);
    }

    public function backupDelete(string $file): RedirectResponse
    {
        $this->authorizeSettings();
        unlink($this->backupPath($file));
        \App\Models\AuditLog::record('Settings', 'Backup Deleted', "Backup file {$file} deleted");

        return redirect()->route('settings.index', ['tab' => 'backups'])
            ->with('status', "Backup {$file} deleted.");
    }

    /**
     * Restore the database from a backup file. Every table is
     * replaced by the backup's content.
     */
    public function backupRestore(string $file): RedirectResponse
    {
        $this->authorizeSettings();

        DB::unprepared(file_get_contents($this->backupPath($file)));
        Cache::flush();
        \App\Models\AuditLog::record('Settings', 'Backup Restored', "Database restored from {$file}");

        return redirect()->route('settings.index', ['tab' => 'backups'])
            ->with('status', "Database restored from {$file}.");
    }

    /**
     * Sanitize the file name and resolve it inside the backup folder.
     */
    private function backupPath(string $file): string
    {
        abort_unless(preg_match('/^backup-[\w\-]+\.sql$/', $file), 404);

        $path = Storage::path('backups/'.$file);
        abort_unless(is_file($path), 404);

        return $path;
    }

    /**
     * Backup history for the table.
     *
     * @return array<int, array{name: string, date: \Illuminate\Support\Carbon, size: string}>
     */
    private function backupFiles(): array
    {
        return collect(Storage::files('backups'))
            ->filter(fn ($f) => str_ends_with($f, '.sql'))
            ->map(fn ($f) => [
                'name' => basename($f),
                'date' => \Illuminate\Support\Carbon::createFromTimestamp(Storage::lastModified($f)),
                'size' => round(Storage::size($f) / 1024).' KB',
            ])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * Read-only system information cards.
     *
     * @return array<string, array{value: string, ok: bool}>
     */
    private function systemInfo(): array
    {
        $cacheOk = Cache::put('settings.healthcheck', 'ok', 5) && Cache::get('settings.healthcheck') === 'ok';
        $storageUsed = $this->directorySize(storage_path());

        return [
            'Laravel Version' => ['value' => app()->version(), 'ok' => true],
            'PHP Version' => ['value' => PHP_VERSION, 'ok' => true],
            'Database' => ['value' => rescue(fn () => DB::connection()->getDriverName().' '.DB::select('SELECT VERSION() as v')[0]->v, DB::connection()->getDriverName(), false), 'ok' => true],
            'Server Time' => ['value' => now()->format('Y-m-d H:i:s (e)'), 'ok' => true],
            'Environment' => ['value' => app()->environment().(config('app.debug') ? ' · debug ON' : ''), 'ok' => ! config('app.debug')],
            'Storage Usage' => ['value' => round($storageUsed / 1048576).' MB used · '.round(disk_free_space(base_path()) / 1073741824).' GB free', 'ok' => true],
            'Memory Usage' => ['value' => round(memory_get_peak_usage(true) / 1048576, 1).' MB (peak)', 'ok' => true],
            'Cache Status' => ['value' => $cacheOk ? 'Working ('.config('cache.default').')' : 'Failing', 'ok' => $cacheOk],
            'Queue Status' => ['value' => config('queue.default').' · '.DB::table('jobs')->count().' pending jobs', 'ok' => true],
        ];
    }

    private function directorySize(string $dir): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
            $size += $f->getSize();
        }

        return $size;
    }

    private function authorizeSettings(): void
    {
        abort_unless(auth()->user()->role->canManageSettings(), 403);
    }
}
