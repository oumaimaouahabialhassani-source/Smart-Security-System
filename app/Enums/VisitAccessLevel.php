<?php

namespace App\Enums;

enum VisitAccessLevel: string
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
}
