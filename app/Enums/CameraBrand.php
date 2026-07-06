<?php

namespace App\Enums;

enum CameraBrand: string
{
    case Hikvision = 'hikvision';
    case Dahua = 'dahua';
    case Axis = 'axis';
    case Uniview = 'uniview';

    public function label(): string
    {
        return match ($this) {
            self::Hikvision => 'Hikvision',
            self::Dahua => 'Dahua',
            self::Axis => 'Axis',
            self::Uniview => 'Uniview',
        };
    }
}
