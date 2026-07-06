<?php

namespace App\Enums;

enum CameraType: string
{
    case Ip = 'ip';
    case Dome = 'dome';
    case Bullet = 'bullet';
    case Ptz = 'ptz';
    case Thermal = 'thermal';

    public function label(): string
    {
        return match ($this) {
            self::Ip => 'IP Camera',
            self::Dome => 'Dome Camera',
            self::Bullet => 'Bullet Camera',
            self::Ptz => 'PTZ Camera',
            self::Thermal => 'Thermal Camera',
        };
    }
}
