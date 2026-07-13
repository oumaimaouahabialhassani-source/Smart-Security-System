<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * The one unrestricted role. Also honored globally through
     * Gate::before in AppServiceProvider.
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Admin-level check kept as the single seam every capability
     * routes through — reintroducing management roles later means
     * touching this file only.
     */
    public function isAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Can create, edit and delete system users.
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can change another user's role. Super Admin only.
     */
    public function canAssignRoles(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * The roles the Super Admin may set through the promote/demote
     * action. Account creation never uses this — new accounts are
     * always Viewer.
     *
     * @return list<self>
     */
    public function assignableRoles(): array
    {
        return $this === self::SuperAdmin ? [self::SuperAdmin, self::Viewer] : [];
    }

    /**
     * Can register and configure cameras and IoT devices.
     */
    public function canManageHardware(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can register new visitors and edit visit information.
     */
    public function canManageVisitors(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can check visitors in and out of the building.
     */
    public function canProcessVisits(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can delete visit records.
     */
    public function canDeleteVisits(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can enroll biometrics and run identity verifications.
     */
    public function canManageBiometrics(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can configure, restart, sync or remove biometric devices,
     * and delete biometric records.
     */
    public function canAdministerBiometrics(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can create and edit access permissions and control doors.
     */
    public function canManageAccess(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can grant temporary visitor access.
     */
    public function canGrantTemporaryAccess(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can delete access permissions and lock all doors at once.
     */
    public function canAdministerAccess(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can acknowledge, assign, annotate and resolve alerts (regular
     * and AI). Viewers monitor read-only.
     */
    public function canManageAlerts(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can open and modify system settings and backups.
     */
    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can consult the audit trail.
     */
    public function canViewAuditLogs(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can open the Reports & Analytics module (read-only for Viewer).
     */
    public function canViewReports(): bool
    {
        return true;
    }

    /**
     * Can open the AI Security Bot module (dashboard, alerts,
     * history, analytics). Mutations require canManageAlerts().
     */
    public function canUseAiBot(): bool
    {
        return true;
    }

    /**
     * Can talk to the AI Chat Assistant.
     */
    public function canUseAiAssistant(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Can open the Visitors module (read).
     */
    public function canViewVisitors(): bool
    {
        return true;
    }

    /**
     * Can open the Access Control module (read).
     */
    public function canViewAccess(): bool
    {
        return true;
    }

    /**
     * Can open the IoT Devices module (read).
     */
    public function canViewDevices(): bool
    {
        return true;
    }

    /**
     * Can open the Biometrics module (read).
     */
    public function canViewBiometrics(): bool
    {
        return true;
    }

    /**
     * Fanned in on monitoring notifications (camera offline, unknown
     * face, forced door, motion, emergencies).
     *
     * @return list<self>
     */
    public static function monitoringRoles(): array
    {
        return [self::SuperAdmin, self::Viewer];
    }
}
