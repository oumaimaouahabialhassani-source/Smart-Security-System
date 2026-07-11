<?php

namespace App\Observers;

use App\Models\AccessEvent;
use App\Models\AccessPermission;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\BiometricProfile;
use App\Models\BiometricVerification;
use App\Models\Camera;
use App\Models\Device;
use App\Models\Door;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Model;

/**
 * One observer audits every business model: plain CRUD entries by
 * default, and domain-specific wording for meaningful transitions
 * (check-ins, enrollments, door locks, suspensions…).
 */
class Auditable
{
    private const MODULES = [
        User::class => 'Users',
        Camera::class => 'Cameras',
        Device::class => 'IoT Devices',
        Visit::class => 'Visitors',
        BiometricProfile::class => 'Biometrics',
        BiometricVerification::class => 'Biometrics',
        AccessPermission::class => 'Access Control',
        AccessEvent::class => 'Access Control',
        Door::class => 'Access Control',
        Alert::class => 'Alerts',
        Setting::class => 'Settings',
    ];

    /**
     * Attribute changes that are system noise, not admin actions.
     */
    private const IGNORED_FIELDS = [
        'updated_at', 'last_login', 'remember_token', 'notification_preferences',
        'last_seen', 'last_activity_at', 'resolved_at',
    ];

    /**
     * One "Settings Updated" entry per request, not one per key.
     */
    private static bool $settingsLogged = false;

    public function created(Model $model): void
    {
        // High-volume event streams get domain wording instead of "created".
        if ($model instanceof AccessEvent) {
            if ($model->kind === 'access') {
                $granted = $model->result === \App\Enums\AccessResult::Granted;
                AuditLog::record('Access Control', $granted ? 'Access Granted' : 'Access Denied',
                    "{$model->person_name} — ".($model->door?->name ?? 'door').' ('.($model->result?->label() ?? '—').')',
                    $granted ? 'success' : 'failed');
            }

            return;
        }

        if ($model instanceof BiometricVerification) {
            $ok = $model->result === \App\Enums\BiometricResult::Success;
            AuditLog::record('Biometrics', $ok ? 'Verification Success' : 'Verification Failed',
                "{$model->subject_name} via {$model->method->label()}", $ok ? 'success' : 'failed');

            return;
        }

        if ($model instanceof Setting) {
            $this->logSettingsOnce($model);

            return;
        }

        AuditLog::record($this->module($model), $this->name($model).' Created', $this->label($model).' was created');
    }

    public function updated(Model $model): void
    {
        if ($model instanceof Setting) {
            $this->logSettingsOnce($model);

            return;
        }

        $changes = array_diff(array_keys($model->getChanges()), self::IGNORED_FIELDS);

        if ($changes === []) {
            return; // background noise (login timestamps, heartbeats…)
        }

        foreach ($this->transitions($model, $changes) as [$action, $description]) {
            AuditLog::record($this->module($model), $action, $description);
        }
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof AccessEvent || $model instanceof BiometricVerification || $model instanceof Setting) {
            return;
        }

        AuditLog::record($this->module($model), $this->name($model).' Deleted', $this->label($model).' was deleted');
    }

    /**
     * Domain-specific wording for status transitions; falls back to
     * a single generic "Updated" entry.
     *
     * @param list<string> $changes
     * @return list<array{0: string, 1: string}>
     */
    private function transitions(Model $model, array $changes): array
    {
        $label = $this->label($model);

        if ($model instanceof Visit && in_array('status', $changes, true)) {
            return match ($model->status) {
                \App\Enums\VisitStatus::Inside => [['Visitor Check-in', "{$label} checked in (badge {$model->badge_number})"]],
                \App\Enums\VisitStatus::CheckedOut => [['Visitor Check-out', "{$label} checked out"]],
                default => [['Visitor Updated', "{$label} status changed to {$model->status->label()}"]],
            };
        }

        if ($model instanceof Door && in_array('status', $changes, true)) {
            return [['Door '.$model->status->label(), "{$label} is now ".strtolower($model->status->label())]];
        }

        if ($model instanceof Alert && in_array('status', $changes, true)) {
            return [['Alert '.$model->status->label(), "{$label} moved to ".$model->status->label()]];
        }

        if ($model instanceof User) {
            $entries = [];
            if (in_array('role', $changes, true)) {
                $entries[] = ['Role Changed', "{$label} is now ".$model->role->label()];
            }
            if (in_array('status', $changes, true)) {
                $entries[] = ['User '.$model->status->label(), "{$label} account is now ".strtolower($model->status->label())];
            }
            if (in_array('password', $changes, true)) {
                $entries[] = ['Password Changed', "{$label} password was changed"];
            }

            return $entries !== [] ? $entries : [['User Updated', "{$label} profile was updated"]];
        }

        if ($model instanceof BiometricProfile) {
            $entries = [];
            foreach (['face_enrolled_at' => 'Face Registered', 'fingerprint_enrolled_at' => 'Fingerprint Registered', 'iris_enrolled_at' => 'Iris Registered'] as $field => $action) {
                if (in_array($field, $changes, true) && $model->{$field} !== null) {
                    $entries[] = [$action, "{$label} — biometric template saved"];
                }
            }

            return $entries !== [] ? $entries : [['Profile Updated', "{$label} biometric profile was updated"]];
        }

        if (($model instanceof Camera || $model instanceof Device) && in_array('status', $changes, true)) {
            return [[$this->name($model).' Status Changed', "{$label} is now ".$model->status->label()]];
        }

        return [[$this->name($model).' Updated', "{$label} was updated"]];
    }

    private function logSettingsOnce(Setting $setting): void
    {
        if (self::$settingsLogged) {
            return;
        }

        self::$settingsLogged = true;
        AuditLog::record('Settings', 'Settings Updated', "The '{$setting->group}' settings group was modified");
    }

    private function module(Model $model): string
    {
        return self::MODULES[$model::class] ?? class_basename($model);
    }

    private function name(Model $model): string
    {
        return match ($model::class) {
            Visit::class => 'Visitor',
            BiometricProfile::class => 'Biometric Profile',
            AccessPermission::class => 'Permission',
            Device::class => 'Device',
            default => class_basename($model),
        };
    }

    private function label(Model $model): string
    {
        return match ($model::class) {
            User::class => $model->name,
            Visit::class => "{$model->full_name} ({$model->visit_code})",
            BiometricProfile::class => ($model->user?->name ?? 'Employee')." ({$model->employee_code})",
            AccessPermission::class => $model->holderName()." (badge {$model->badge_id})",
            Alert::class => "{$model->type} ({$model->alert_code})",
            default => $model->name ?? class_basename($model).' #'.$model->getKey(),
        };
    }
}
