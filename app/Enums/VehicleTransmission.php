<?php

namespace App\Enums;

enum VehicleTransmission: string
{
    case MANUAL = 'manual';
    case AUTOMATIC = 'automatic';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::AUTOMATIC => 'Automatic',
        };
    }

    public static function values(): array
    {
        return array_map(fn (\App\Enums\VehicleTransmission $case) => $case->value, self::cases());
    }
}
