<?php

namespace App\Enums;

enum VehicleFuelType: string
{
    case PETROL = 'petrol';
    case DIESEL = 'diesel';
    case ELECTRIC = 'electric';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::PETROL => 'Petrol',
            self::DIESEL => 'Diesel',
            self::ELECTRIC => 'Electric',
            self::HYBRID => 'Hybrid',
        };
    }

    public static function values(): array
    {
        return array_map(fn (\App\Enums\VehicleFuelType $case) => $case->value, self::cases());
    }
}
