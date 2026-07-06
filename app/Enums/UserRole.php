<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case SecurityOfficer = 'security_officer';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::SecurityOfficer => 'Security Officer',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }
}
