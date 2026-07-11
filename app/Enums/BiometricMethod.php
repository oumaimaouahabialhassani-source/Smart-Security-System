<?php

namespace App\Enums;

enum BiometricMethod: string
{
    case Face = 'face';
    case Fingerprint = 'fingerprint';
    case Iris = 'iris';

    public function label(): string
    {
        return match ($this) {
            self::Face => 'Face Recognition',
            self::Fingerprint => 'Fingerprint',
            self::Iris => 'Iris Scan',
        };
    }

    /**
     * Icon glyph used in tables and logs.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Face => '◉',
            self::Fingerprint => '❋',
            self::Iris => '◎',
        };
    }
}
