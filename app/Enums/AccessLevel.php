<?php

namespace App\Enums;

enum AccessLevel: string
{
    case Reception = 'reception';
    case Offices = 'offices';
    case Laboratory = 'laboratory';
    case ServerRoom = 'server_room';
    case FullAccess = 'full_access';

    public function label(): string
    {
        return match ($this) {
            self::Reception => 'Reception',
            self::Offices => 'Offices',
            self::Laboratory => 'Laboratory',
            self::ServerRoom => 'Server Room',
            self::FullAccess => 'Full Access',
        };
    }

    /**
     * Hierarchy rank: a permission grants a door when its rank is at
     * least the door's required rank.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Reception => 1,
            self::Offices => 2,
            self::Laboratory => 3,
            self::ServerRoom => 4,
            self::FullAccess => 5,
        };
    }
}
