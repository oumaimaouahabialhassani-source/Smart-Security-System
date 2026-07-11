<?php

namespace App\Enums;

enum BiometricResult: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Warning = 'warning';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Warning => 'Warning',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Success => 'badge-success',
            self::Failed => 'badge-danger',
            self::Warning => 'badge-warning',
        };
    }
}
