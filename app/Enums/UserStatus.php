<?php

namespace App\Enums;

enum UserStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ACTIVE = 'active';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::ACTIVE => 'Active',
        };
    }

    public static function values(): array
    {
        return array_map(fn (\App\Enums\UserStatus $case) => $case->value, self::cases());
    }
}
