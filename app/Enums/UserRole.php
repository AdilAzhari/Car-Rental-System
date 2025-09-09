<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case OWNER = 'owner';
    case RENTER = 'renter';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::OWNER => 'Vehicle Owner',
            self::RENTER => 'Renter',
        };
    }

    public static function values(): array
    {
        return array_map(fn (\App\Enums\UserRole $case) => $case->value, self::cases());
    }
}
