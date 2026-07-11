<?php

namespace App\Enums;

enum AccessResult: string
{
    case Granted = 'granted';
    case Denied = 'denied';
    case Unauthorized = 'unauthorized';
    case ExpiredBadge = 'expired_badge';
    case FaceNotRecognized = 'face_not_recognized';
    case FingerprintFailed = 'fingerprint_failed';

    public function label(): string
    {
        return match ($this) {
            self::Granted => 'Granted',
            self::Denied => 'Denied',
            self::Unauthorized => 'Unauthorized',
            self::ExpiredBadge => 'Expired Badge',
            self::FaceNotRecognized => 'Face Not Recognized',
            self::FingerprintFailed => 'Fingerprint Failed',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Granted => 'badge-success',
            self::ExpiredBadge => 'badge-warning',
            default => 'badge-danger',
        };
    }
}
