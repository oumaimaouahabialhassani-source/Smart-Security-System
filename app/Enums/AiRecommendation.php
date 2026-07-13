<?php

namespace App\Enums;

enum AiRecommendation: string
{
    case NotifySecurityTeam = 'notify_security_team';
    case LockDoor = 'lock_door';
    case VerifyIdentity = 'verify_identity';
    case ReviewFootage = 'review_footage';
    case ContactAdministrator = 'contact_administrator';
    case DispatchTechnician = 'dispatch_technician';
    case Ignore = 'ignore';

    public function label(): string
    {
        return match ($this) {
            self::NotifySecurityTeam => 'Notify Security Team',
            self::LockDoor => 'Lock the Door',
            self::VerifyIdentity => 'Verify Identity',
            self::ReviewFootage => 'Review Camera Footage',
            self::ContactAdministrator => 'Contact Administrator',
            self::DispatchTechnician => 'Dispatch Technician',
            self::Ignore => 'Ignore (False Positive)',
        };
    }

    /**
     * CSS badge modifier used by the views.
     */
    public function badge(): string
    {
        return match ($this) {
            self::LockDoor, self::ContactAdministrator => 'badge-danger',
            self::NotifySecurityTeam, self::VerifyIdentity, self::ReviewFootage, self::DispatchTechnician => 'badge-warning',
            self::Ignore => 'badge-muted',
        };
    }
}
