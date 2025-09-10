<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';
    case MAINTENANCE  = 'maintenance';
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('enums.vehicle_status.pending'),
            self::APPROVED => __('enums.vehicle_status.approved'),
            self::REJECTED => __('enums.vehicle_status.rejected'),
            self::PUBLISHED => __('enums.vehicle_status.published'),
            self::MAINTENANCE => __('enums.vehicle_status.maintenance'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::PUBLISHED => 'success',
            self::MAINTENANCE => 'primary',
        };
    }

    public static function values(): array
    {
        return array_map(fn (VehicleStatus $case) => $case->value, self::cases());
    }
}
