<?php

namespace App\Enums;

enum DeviceProtocol: string
{
    case Wifi = 'wifi';
    case Ethernet = 'ethernet';
    case Zigbee = 'zigbee';
    case ZWave = 'zwave';
    case Bluetooth = 'bluetooth';
    case LoRaWan = 'lorawan';
    case Rs485 = 'rs485';
    case Poe = 'poe';

    public function label(): string
    {
        return match ($this) {
            self::Wifi => 'Wi-Fi',
            self::Ethernet => 'Ethernet',
            self::Zigbee => 'Zigbee',
            self::ZWave => 'Z-Wave',
            self::Bluetooth => 'Bluetooth',
            self::LoRaWan => 'LoRaWAN',
            self::Rs485 => 'RS-485',
            self::Poe => 'PoE',
        };
    }
}
