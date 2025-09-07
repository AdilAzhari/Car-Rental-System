<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PUBLISHED => 'Published',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::PUBLISHED => 'success',
        };
    }

    public static function values(): array
    {
        return array_map(fn (\App\Enums\VehicleStatus $case) => $case->value, self::cases());
    }
}
