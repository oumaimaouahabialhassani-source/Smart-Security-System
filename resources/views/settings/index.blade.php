@extends('layouts.app')

@section('title', 'Settings — ' . config('app.name'))

@php($g = fn (string $group, string $key, $default = null) => $values[$group][$key] ?? $default)

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">System configuration, security policies, appearance and backups.</p>
        </div>
    </div>

    <section class="panel">
        <div class="tabs tabs-wrap" role="tablist" aria-label="Settings sections">
            @foreach (['general' => 'General', 'security' => 'Security', 'notifications' => 'Notifications', 'cameras' => 'Cameras', 'biometrics' => 'Biometrics', 'devices' => 'IoT Devices', 'appearance' => 'Appearance', 'mail' => 'Email', 'backups' => 'Backups', 'system' => 'System Info'] as $key => $label)
                <button type="button" class="tab" role="tab" data-tab="{{ $key }}" aria-selected="false">{{ $label }}</button>
            @endforeach
        </div>

        {{-- ============ General ============ --}}
        <div class="tab-panel" id="tab-general" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'general') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="company_name">Company Name <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="company_name" name="company_name" required maxlength="100"
                               value="{{ old('company_name', $g('general', 'company_name', config('app.name'))) }}"
                               @class(['is-invalid' => $errors->has('company_name')])>
                        @error('company_name') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="company_email">Company Email</label>
                        <input type="email" id="company_email" name="company_email" value="{{ old('company_email', $g('general', 'company_email')) }}" placeholder="contact@company.com">
                        @error('company_email') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="company_phone">Company Phone</label>
                        <input type="tel" id="company_phone" name="company_phone" value="{{ old('company_phone', $g('general', 'company_phone')) }}" placeholder="+212 500 000 000">
                    </div>
                    <div class="form-field">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="{{ old('website', $g('general', 'website')) }}" placeholder="https://company.com">
                        @error('website') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field form-field-full">
                        <label for="company_address">Company Address</label>
                        <input type="text" id="company_address" name="company_address" value="{{ old('company_address', $g('general', 'company_address')) }}" placeholder="Street, city, country">
                    </div>
                    <div class="form-field">
                        <label for="timezone">Timezone <span class="req" aria-hidden="true">*</span></label>
                        <select id="timezone" name="timezone" required>
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" @selected($g('general', 'timezone', 'Africa/Casablanca') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <option value="en" @selected($g('general', 'language', 'en') === 'en')>English</option>
                            <option value="fr" @selected($g('general', 'language') === 'fr')>Français</option>
                            <option value="ar" @selected($g('general', 'language') === 'ar')>العربية</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="date_format">Date Format</label>
                        <select id="date_format" name="date_format">
                            <option value="M j, Y" @selected($g('general', 'date_format', 'M j, Y') === 'M j, Y')>{{ now()->format('M j, Y') }}</option>
                            <option value="Y-m-d" @selected($g('general', 'date_format') === 'Y-m-d')>{{ now()->format('Y-m-d') }}</option>
                            <option value="d/m/Y" @selected($g('general', 'date_format') === 'd/m/Y')>{{ now()->format('d/m/Y') }}</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="time_format">Time Format</label>
                        <select id="time_format" name="time_format">
                            <option value="H:i" @selected($g('general', 'time_format', 'H:i') === 'H:i')>{{ now()->format('H:i') }} (24h)</option>
                            <option value="h:i A" @selected($g('general', 'time_format') === 'h:i A')>{{ now()->format('h:i A') }} (12h)</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ route('settings.index', ['tab' => 'general']) }}" class="btn btn-ghost">Reset</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ Security ============ --}}
        <div class="tab-panel" id="tab-security" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'security') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="session_timeout">Session Timeout (minutes)</label>
                        <input type="number" id="session_timeout" name="session_timeout" min="5" max="1440" required
                               value="{{ old('session_timeout', $g('security', 'session_timeout', 120)) }}">
                        <p class="label-hint">Idle sessions expire after this delay.</p>
                    </div>
                    <div class="form-field">
                        <label for="password_min_length">Password Minimum Length</label>
                        <input type="number" id="password_min_length" name="password_min_length" min="8" max="64" required
                               value="{{ old('password_min_length', $g('security', 'password_min_length', 8)) }}">
                        <p class="label-hint">Applied to invitations and password resets.</p>
                    </div>
                    <div class="form-field">
                        <label for="password_expiration_days">Password Expiration (days)</label>
                        <input type="number" id="password_expiration_days" name="password_expiration_days" min="0" max="365" required
                               value="{{ old('password_expiration_days', $g('security', 'password_expiration_days', 0)) }}">
                        <p class="label-hint">0 = passwords never expire.</p>
                    </div>
                    <div class="form-field">
                        <label for="max_login_attempts">Maximum Login Attempts</label>
                        <input type="number" id="max_login_attempts" name="max_login_attempts" min="3" max="20" required
                               value="{{ old('max_login_attempts', $g('security', 'max_login_attempts', 5)) }}">
                        <p class="label-hint">Applied live on the login form.</p>
                    </div>
                    <div class="form-field">
                        <label for="lock_duration_minutes">Account Lock Duration (minutes)</label>
                        <input type="number" id="lock_duration_minutes" name="lock_duration_minutes" min="1" max="1440" required
                               value="{{ old('lock_duration_minutes', $g('security', 'lock_duration_minutes', 1)) }}">
                    </div>
                </div>
                <div class="toggle-grid">
                    <x-toggle name="password_require_uppercase" label="Require Uppercase" hint="Passwords must contain A–Z" :checked="$g('security', 'password_require_uppercase', false)" />
                    <x-toggle name="password_require_numbers" label="Require Numbers" hint="Passwords must contain 0–9" :checked="$g('security', 'password_require_numbers', false)" />
                    <x-toggle name="password_require_symbols" label="Require Symbols" hint="Passwords must contain !@#…" :checked="$g('security', 'password_require_symbols', false)" />
                    <x-toggle name="auto_logout" label="Auto Logout" hint="Force logout when the session times out" :checked="$g('security', 'auto_logout', true)" />
                    <x-toggle name="two_factor" label="Two-Factor Authentication" hint="Stored — requires an authenticator integration" :checked="$g('security', 'two_factor', false)" />
                </div>
                <div class="form-actions">
                    <a href="{{ route('settings.index', ['tab' => 'security']) }}" class="btn btn-ghost">Reset</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ Notifications ============ --}}
        <div class="tab-panel" id="tab-notifications" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'notifications') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="toggle-grid">
                    <x-toggle name="email" label="Email Notifications" :checked="$g('notifications', 'email', true)" />
                    <x-toggle name="sms" label="SMS Notifications" hint="Requires an SMS gateway" :checked="$g('notifications', 'sms', false)" />
                    <x-toggle name="push" label="Push Notifications" :checked="$g('notifications', 'push', false)" />
                    <x-toggle name="visitor_alerts" label="Visitor Alerts" hint="Blacklist, overstay, forgotten check-out" :checked="$g('notifications', 'visitor_alerts', true)" />
                    <x-toggle name="access_denied_alerts" label="Access Denied Alerts" :checked="$g('notifications', 'access_denied_alerts', true)" />
                    <x-toggle name="camera_offline_alerts" label="Camera Offline Alerts" :checked="$g('notifications', 'camera_offline_alerts', true)" />
                    <x-toggle name="device_offline_alerts" label="Device Offline Alerts" :checked="$g('notifications', 'device_offline_alerts', true)" />
                    <x-toggle name="critical_alerts" label="Critical Security Alerts" hint="Always recommended" :checked="$g('notifications', 'critical_alerts', true)" />
                </div>
                <p class="muted tab-note">Global switches — individual users refine their own channels from the Alerts page.</p>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ Cameras ============ --}}
        <div class="tab-panel" id="tab-cameras" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'cameras') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="recording_quality">Default Recording Quality</label>
                        <select id="recording_quality" name="recording_quality">
                            @foreach (['720p', '1080p', '1440p', '4K'] as $q)
                                <option value="{{ $q }}" @selected($g('cameras', 'recording_quality', '1080p') === $q)>{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="stream_quality">Stream Quality</label>
                        <select id="stream_quality" name="stream_quality">
                            @foreach (['low' => 'Low (bandwidth saver)', 'medium' => 'Medium', 'high' => 'High'] as $v2 => $l)
                                <option value="{{ $v2 }}" @selected($g('cameras', 'stream_quality', 'medium') === $v2)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="snapshot_quality">Snapshot Quality (%)</label>
                        <input type="number" id="snapshot_quality" name="snapshot_quality" min="10" max="100" required
                               value="{{ old('snapshot_quality', $g('cameras', 'snapshot_quality', 80)) }}">
                    </div>
                    <div class="form-field">
                        <label for="retention_days">Recording Retention (days)</label>
                        <input type="number" id="retention_days" name="retention_days" min="1" max="365" required
                               value="{{ old('retention_days', $g('cameras', 'retention_days', 30)) }}">
                    </div>
                    <div class="form-field">
                        <label for="max_storage_gb">Maximum Storage (GB)</label>
                        <input type="number" id="max_storage_gb" name="max_storage_gb" min="10" max="10000" required
                               value="{{ old('max_storage_gb', $g('cameras', 'max_storage_gb', 500)) }}">
                    </div>
                </div>
                <div class="toggle-grid">
                    <x-toggle name="motion_detection" label="Motion Detection" hint="Cameras record when movement is detected" :checked="$g('cameras', 'motion_detection', true)" />
                    <x-toggle name="auto_recording" label="Auto Recording" hint="Record continuously on all cameras" :checked="$g('cameras', 'auto_recording', true)" />
                </div>
                <p class="muted tab-note">These defaults apply when camera hardware is connected — per-camera options stay in the Cameras module.</p>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ Biometrics ============ --}}
        <div class="tab-panel" id="tab-biometrics" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'biometrics') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="face_threshold">Face Recognition Threshold (%)</label>
                        <input type="number" id="face_threshold" name="face_threshold" min="50" max="100" required
                               value="{{ old('face_threshold', $g('biometrics', 'face_threshold', 85)) }}">
                        <p class="label-hint">Minimum match score to grant access. Higher = stricter, fewer false positives.</p>
                    </div>
                    <div class="form-field">
                        <label for="fingerprint_threshold">Fingerprint Matching Threshold (%)</label>
                        <input type="number" id="fingerprint_threshold" name="fingerprint_threshold" min="50" max="100" required
                               value="{{ old('fingerprint_threshold', $g('biometrics', 'fingerprint_threshold', 80)) }}">
                        <p class="label-hint">Minimum ridge-match score accepted by the scanners.</p>
                    </div>
                    <div class="form-field">
                        <label for="enrollment_quality">Minimum Enrollment Quality (%)</label>
                        <input type="number" id="enrollment_quality" name="enrollment_quality" min="40" max="100" required
                               value="{{ old('enrollment_quality', $g('biometrics', 'enrollment_quality', 60)) }}">
                        <p class="label-hint">Captures below this quality must be retaken during enrollment.</p>
                    </div>
                    <div class="form-field">
                        <label for="max_retry_attempts">Maximum Retry Attempts</label>
                        <input type="number" id="max_retry_attempts" name="max_retry_attempts" min="1" max="10" required
                               value="{{ old('max_retry_attempts', $g('biometrics', 'max_retry_attempts', 3)) }}">
                        <p class="label-hint">Failed attempts before the reader locks and raises an alert.</p>
                    </div>
                </div>
                <div class="toggle-grid">
                    <x-toggle name="anti_spoofing" label="Anti-Spoofing Detection" hint="Reject photos, masks and fake fingerprints (liveness check)" :checked="$g('biometrics', 'anti_spoofing', true)" />
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ IoT Devices ============ --}}
        <div class="tab-panel" id="tab-devices" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'devices') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="heartbeat_seconds">Device Heartbeat Interval (seconds)</label>
                        <input type="number" id="heartbeat_seconds" name="heartbeat_seconds" min="10" max="3600" required
                               value="{{ old('heartbeat_seconds', $g('devices', 'heartbeat_seconds', 60)) }}">
                    </div>
                    <div class="form-field">
                        <label for="scan_interval_minutes">Device Scan Interval (minutes)</label>
                        <input type="number" id="scan_interval_minutes" name="scan_interval_minutes" min="1" max="1440" required
                               value="{{ old('scan_interval_minutes', $g('devices', 'scan_interval_minutes', 15)) }}">
                    </div>
                    <div class="form-field">
                        <label for="offline_timeout_minutes">Offline Timeout (minutes)</label>
                        <input type="number" id="offline_timeout_minutes" name="offline_timeout_minutes" min="1" max="1440" required
                               value="{{ old('offline_timeout_minutes', $g('devices', 'offline_timeout_minutes', 5)) }}">
                        <p class="label-hint">A silent device is flagged Offline after this delay.</p>
                    </div>
                </div>
                <div class="toggle-grid">
                    <x-toggle name="auto_reconnect" label="Automatic Reconnection" :checked="$g('devices', 'auto_reconnect', true)" />
                    <x-toggle name="auto_sync" label="Device Synchronization" hint="Push templates and schedules automatically" :checked="$g('devices', 'auto_sync', true)" />
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ Appearance ============ --}}
        <div class="tab-panel" id="tab-appearance" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'appearance') }}" enctype="multipart/form-data" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme">
                            <option value="system" @selected($g('appearance', 'theme', 'system') === 'system')>System (follow OS)</option>
                            <option value="dark" @selected($g('appearance', 'theme') === 'dark')>Dark Mode</option>
                            <option value="light" @selected($g('appearance', 'theme') === 'light')>Light Mode</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="accent_color">Primary / Accent Color</label>
                        <input type="color" id="accent_color" name="accent_color" class="color-input"
                               value="{{ old('accent_color', $g('appearance', 'accent_color', '#34d399')) }}">
                        <p class="label-hint">Previewed live — buttons, charts and badges follow this color.</p>
                    </div>
                    <div class="form-field">
                        <label for="sidebar_style">Sidebar Style</label>
                        <select id="sidebar_style" name="sidebar_style">
                            <option value="default" @selected($g('appearance', 'sidebar_style', 'default') === 'default')>Default</option>
                            <option value="compact" @selected($g('appearance', 'sidebar_style') === 'compact')>Compact</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="dashboard_layout">Dashboard Layout</label>
                        <select id="dashboard_layout" name="dashboard_layout">
                            <option value="default" @selected($g('appearance', 'dashboard_layout', 'default') === 'default')>Default</option>
                            <option value="wide" @selected($g('appearance', 'dashboard_layout') === 'wide')>Wide (full width)</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="logo">Company Logo <span class="label-hint">(PNG/SVG, max 1 MB)</span></label>
                        <div class="avatar-upload">
                            <img id="logo-preview" src="{{ $g('appearance', 'logo', '') }}" alt="" class="brand-preview" @if (! $g('appearance', 'logo')) hidden @endif>
                            <input type="file" id="logo" name="logo" accept="image/*">
                        </div>
                        @error('logo') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="favicon">Favicon <span class="label-hint">(max 256 KB)</span></label>
                        <div class="avatar-upload">
                            <img id="favicon-preview" src="{{ $g('appearance', 'favicon', '') }}" alt="" class="brand-preview brand-preview-sm" @if (! $g('appearance', 'favicon')) hidden @endif>
                            <input type="file" id="favicon" name="favicon" accept="image/*">
                        </div>
                        @error('favicon') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="form-actions">
                    <a href="{{ route('settings.index', ['tab' => 'appearance']) }}" class="btn btn-ghost">Reset</a>
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- ============ Email ============ --}}
        <div class="tab-panel" id="tab-mail" role="tabpanel" hidden>
            <form method="POST" action="{{ route('settings.update', 'mail') }}" data-loading>
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="form-field">
                        <label for="driver">Mail Driver</label>
                        <select id="driver" name="driver">
                            <option value="log" @selected($g('mail', 'driver', config('mail.default')) === 'log')>Log (development — writes to laravel.log)</option>
                            <option value="smtp" @selected($g('mail', 'driver', config('mail.default')) === 'smtp')>SMTP</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="host">SMTP Host</label>
                        <input type="text" id="host" name="host" value="{{ old('host', $g('mail', 'host')) }}" placeholder="smtp.mailprovider.com">
                    </div>
                    <div class="form-field">
                        <label for="port">SMTP Port</label>
                        <input type="number" id="port" name="port" min="1" max="65535" value="{{ old('port', $g('mail', 'port', 587)) }}">
                    </div>
                    <div class="form-field">
                        <label for="encryption">Encryption</label>
                        <select id="encryption" name="encryption">
                            <option value="tls" @selected($g('mail', 'encryption', 'tls') === 'tls')>TLS</option>
                            <option value="ssl" @selected($g('mail', 'encryption') === 'ssl')>SSL</option>
                            <option value="none" @selected($g('mail', 'encryption') === 'none')>None</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="mail-username">Username</label>
                        <input type="text" id="mail-username" name="username" value="{{ old('username', $g('mail', 'username')) }}" autocomplete="off">
                    </div>
                    <div class="form-field">
                        <label for="mail-password">Password <span class="label-hint">(leave blank to keep current — never displayed)</span></label>
                        <input type="password" id="mail-password" name="password" value="" autocomplete="new-password" placeholder="{{ $g('mail', 'password') ? '••••••••' : 'Not set' }}">
                    </div>
                    <div class="form-field">
                        <label for="sender_name">Sender Name</label>
                        <input type="text" id="sender_name" name="sender_name" value="{{ old('sender_name', $g('mail', 'sender_name', config('app.name'))) }}">
                    </div>
                    <div class="form-field">
                        <label for="sender_email">Sender Email</label>
                        <input type="email" id="sender_email" name="sender_email" value="{{ old('sender_email', $g('mail', 'sender_email')) }}" placeholder="no-reply@company.com">
                        @error('sender_email') <p class="field-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" data-loading-text="Saving…">Save Changes</button>
                </div>
            </form>
            <form method="POST" action="{{ route('settings.test-email') }}" class="form-actions form-actions-left" data-loading>
                @csrf
                <button type="submit" class="btn btn-secondary" data-loading-text="Sending…">Send Test Email to {{ auth()->user()->email }}</button>
            </form>
        </div>

        {{-- ============ Backups ============ --}}
        <div class="tab-panel" id="tab-backups" role="tabpanel" hidden>
            <div class="backup-head">
                <form method="POST" action="{{ route('settings.backup-create') }}" data-loading>
                    @csrf
                    <button type="submit" class="btn btn-primary" data-loading-text="Creating backup…">Create Manual Backup</button>
                </form>
                <form method="POST" action="{{ route('settings.update', 'backups') }}" class="backup-toggles" data-loading>
                    @csrf
                    @method('PUT')
                    <x-toggle name="auto_daily" label="Automatic Daily Backup" hint="Every day at 02:00" :checked="$g('backups', 'auto_daily', false)" />
                    <x-toggle name="auto_weekly" label="Automatic Weekly Backup" hint="Mondays at 02:30" :checked="$g('backups', 'auto_weekly', false)" />
                    <x-toggle name="auto_monthly" label="Automatic Monthly Backup" hint="1st of the month at 03:00" :checked="$g('backups', 'auto_monthly', false)" />
                    <button type="submit" class="btn btn-secondary" data-loading-text="Saving…">Save Schedule</button>
                </form>
            </div>
            <p class="muted tab-note">Automatic backups require the scheduler: <span class="mono">php artisan schedule:work</span> (or a cron entry in production).</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Backup Name</th><th>Date</th><th>Size</th><th>Status</th><th class="th-actions">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($backupFiles as $backup)
                            <tr>
                                <td class="mono">{{ $backup['name'] }}</td>
                                <td>{{ $backup['date']->format('M j, Y — H:i') }}</td>
                                <td>{{ $backup['size'] }}</td>
                                <td><span class="badge badge-success"><span class="badge-indicator" aria-hidden="true"></span>Completed</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('settings.backup-download', $backup['name']) }}" class="action-btn" title="Download">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        </a>
                                        <button type="button" class="action-btn js-confirm" title="Restore"
                                                data-action="{{ route('settings.backup-restore', $backup['name']) }}"
                                                data-title="Restore Backup"
                                                data-text="Replace the ENTIRE current database with this backup? Data created since then will be lost."
                                                data-name="{{ $backup['name'] }}" data-btn="Restore">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><polyline points="3 3 3 8 8 8"/></svg>
                                        </button>
                                        <button type="button" class="action-btn action-danger js-delete" title="Delete"
                                                data-action="{{ route('settings.backup-delete', $backup['name']) }}"
                                                data-name="{{ $backup['name'] }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="table-empty">No backups yet — create your first one above.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ System Info ============ --}}
        <div class="tab-panel" id="tab-system" role="tabpanel" hidden>
            <div class="health-grid">
                @foreach ($system as $label => $item)
                    <div class="health-item">
                        <span class="health-dot {{ $item['ok'] ? 'health-ok' : 'health-bad' }}" aria-hidden="true"></span>
                        <div>
                            <span class="health-label">{{ $label }}</span>
                            <span class="health-value">{{ $item['value'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-delete-modal title="Delete Backup" message="Are you sure you want to delete this backup file?" />

    {{-- Confirm modal (restore) --}}
    <div class="modal-backdrop" id="confirm-modal" hidden>
        <div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title">
            <h3 class="modal-title" id="confirm-modal-title">Confirm</h3>
            <p class="modal-text" id="confirm-modal-text"></p>
            <p class="modal-target" id="confirm-modal-name"></p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-confirm>Cancel</button>
                <form method="POST" id="confirm-modal-form" data-loading>
                    @csrf
                    <button type="submit" class="btn btn-danger" id="confirm-modal-btn" data-loading-text="Working…">Confirm</button>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    (() => {
        // Tabs with ?tab= deep link
        const tabs = document.querySelectorAll('.tab');
        const activate = (name) => {
            tabs.forEach((t) => {
                const on = t.dataset.tab === name;
                t.classList.toggle('active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('.tab-panel').forEach((p) => { p.hidden = p.id !== 'tab-' + name; });
        };
        tabs.forEach((t) => t.addEventListener('click', () => {
            activate(t.dataset.tab);
            history.replaceState(null, '', '?tab=' + t.dataset.tab);
        }));
        activate(new URLSearchParams(location.search).get('tab') || 'general');

        // Confirm modal (restore)
        const modal = document.getElementById('confirm-modal');
        const form = document.getElementById('confirm-modal-form');
        document.querySelectorAll('.js-confirm').forEach((btn) => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.action;
                document.getElementById('confirm-modal-title').textContent = btn.dataset.title;
                document.getElementById('confirm-modal-text').textContent = btn.dataset.text;
                document.getElementById('confirm-modal-name').textContent = btn.dataset.name;
                document.getElementById('confirm-modal-btn').textContent = btn.dataset.btn;
                modal.hidden = false;
            });
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-confirm]')) modal.hidden = true;
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') modal.hidden = true; });

        // Live accent color preview
        const accent = document.getElementById('accent_color');
        if (accent) accent.addEventListener('input', () => {
            document.documentElement.style.setProperty('--accent', accent.value);
            document.documentElement.style.setProperty('--accent-dark', accent.value);
        });

        // Logo / favicon preview
        [['logo', 'logo-preview'], ['favicon', 'favicon-preview']].forEach(([inputId, imgId]) => {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) return;
                const img = document.getElementById(imgId);
                img.src = URL.createObjectURL(file);
                img.hidden = false;
            });
        });
    })();
</script>
@endpush
