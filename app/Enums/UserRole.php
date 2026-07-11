<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case SecurityOfficer = 'security_officer';
    case Manager = 'manager';
    case Receptionist = 'receptionist';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::SecurityOfficer => 'Security Officer',
            self::Manager => 'Manager',
            self::Receptionist => 'Receptionist',
            self::Employee => 'Employee',
        };
    }

    /**
     * Can create, edit and delete system users.
     */
    public function canManageUsers(): bool
    {
        return $this === self::Administrator;
    }

    /**
     * Can register and configure cameras and IoT devices.
     */
    public function canManageHardware(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer], true);
    }

    /**
     * Can register new visitors and edit visit information.
     */
    public function canManageVisitors(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer, self::Receptionist], true);
    }

    /**
     * Can check visitors in and out of the building.
     */
    public function canProcessVisits(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer], true);
    }

    /**
     * Can delete visit records.
     */
    public function canDeleteVisits(): bool
    {
        return $this === self::Administrator;
    }

    /**
     * Can enroll biometrics (faces, fingerprints) and run identity
     * verifications.
     */
    public function canManageBiometrics(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer], true);
    }

    /**
     * Can configure, restart, sync or remove biometric devices,
     * and delete biometric records.
     */
    public function canAdministerBiometrics(): bool
    {
        return $this === self::Administrator;
    }

    /**
     * Can create and edit access permissions and control doors.
     */
    public function canManageAccess(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer], true);
    }

    /**
     * Can grant temporary visitor access.
     */
    public function canGrantTemporaryAccess(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer, self::Receptionist], true);
    }

    /**
     * Can delete access permissions and lock all doors at once.
     */
    public function canAdministerAccess(): bool
    {
        return $this === self::Administrator;
    }

    /**
     * Can acknowledge, assign, annotate and resolve alerts.
     */
    public function canManageAlerts(): bool
    {
        return in_array($this, [self::Administrator, self::SecurityOfficer], true);
    }

    /**
     * Can open and modify system settings and backups.
     */
    public function canManageSettings(): bool
    {
        return $this === self::Administrator;
    }

    /**
     * Can consult the audit trail.
     */
    public function canViewAuditLogs(): bool
    {
        return $this === self::Administrator;
    }

    /**
     * Can open the Reports & Analytics module.
     */
    public function canViewReports(): bool
    {
        return in_array($this, [self::Administrator, self::Manager], true);
    }
}
