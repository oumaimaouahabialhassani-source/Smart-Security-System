<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyStoredSettings();
        $this->registerAuditing();
    }

    /**
     * Automatic audit logging: one observer for every business model,
     * plus Laravel's native authentication events.
     */
    private function registerAuditing(): void
    {
        foreach ([
            \App\Models\User::class, \App\Models\Camera::class, \App\Models\Device::class,
            \App\Models\Visit::class, \App\Models\BiometricProfile::class, \App\Models\BiometricVerification::class,
            \App\Models\AccessPermission::class, \App\Models\AccessEvent::class, \App\Models\Door::class,
            \App\Models\Alert::class, Setting::class,
        ] as $model) {
            $model::observe(\App\Observers\Auditable::class);
        }

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            \App\Models\AuditLog::record('Authentication', 'Login', $event->user->name.' signed in', 'success', $event->user);
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user) {
                \App\Models\AuditLog::record('Authentication', 'Logout', $event->user->name.' signed out', 'success', $event->user);
            }
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            \App\Models\AuditLog::record('Authentication', 'Failed Login', 'Failed login attempt for '.($event->credentials['email'] ?? 'unknown email'), 'failed', $event->user);
        });

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\PasswordReset::class, function ($event) {
            \App\Models\AuditLog::record('Authentication', 'Password Reset', $event->user->name.' reset their password', 'success', $event->user);
        });
    }

    /**
     * Override runtime configuration with the values administrators
     * saved in the Settings module. Silently skipped while the
     * database is unavailable (fresh install, migrations running).
     */
    private function applyStoredSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            if ($name = Setting::get('general.company_name')) {
                config(['app.name' => $name]);
            }

            if ($timezone = Setting::get('general.timezone')) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            if ($lifetime = Setting::get('security.session_timeout')) {
                config(['session.lifetime' => (int) $lifetime]);
            }

            $mail = Setting::group('mail');

            if (! empty($mail['host'])) {
                config([
                    'mail.default' => $mail['driver'] ?? config('mail.default'),
                    'mail.mailers.smtp.host' => $mail['host'],
                    'mail.mailers.smtp.port' => (int) ($mail['port'] ?? 587),
                    'mail.mailers.smtp.username' => $mail['username'] ?? null,
                    'mail.mailers.smtp.password' => ! empty($mail['password'])
                        ? decrypt($mail['password'])
                        : config('mail.mailers.smtp.password'),
                    'mail.mailers.smtp.scheme' => ($mail['encryption'] ?? null) === 'none' ? null : ($mail['encryption'] ?? null),
                    'mail.from.name' => $mail['sender_name'] ?? config('mail.from.name'),
                    'mail.from.address' => $mail['sender_email'] ?? config('mail.from.address'),
                ]);
            }
        } catch (\Throwable) {
            // Database not reachable yet — keep the .env configuration.
        }
    }
}
