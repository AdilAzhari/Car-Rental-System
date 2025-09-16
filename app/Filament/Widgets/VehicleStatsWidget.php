<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehicleStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->role === UserRole::ADMIN || $user->role === UserRole::OWNER);
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        // Base query - filter by owner if not admin
        $baseQuery = Vehicle::query();
        if ($user && $user->role === UserRole::OWNER) {
            $baseQuery->where('owner_id', $user->id);
        }

        // Total vehicles
        $totalVehicles = $baseQuery->count();

        // Pending vehicles (awaiting approval)
        $pendingVehicles = (clone $baseQuery)->where('status', VehicleStatus::PENDING)->count();

        // Published vehicles (available for rent)
        $publishedVehicles = (clone $baseQuery)->where('status', VehicleStatus::PUBLISHED)->count();

        // Average daily rate
        $averageRate = (clone $baseQuery)->where('status', VehicleStatus::PUBLISHED)->avg('daily_rate') ?? 0;

        return [
            Stat::make(__('widgets.total_vehicles'), $totalVehicles)
                ->description(__('widgets.all_registered_vehicles'))
                ->descriptionIcon('heroicon-o-truck')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make(__('widgets.pending_approval'), $pendingVehicles)
                ->description(__('widgets.vehicles_awaiting_approval'))
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingVehicles > 0 ? 'warning' : 'success')
                ->chart([2, 5, 3, 8, 2, 1, $pendingVehicles]),

            Stat::make(__('widgets.published'), $publishedVehicles)
                ->description(__('widgets.available_for_booking'))
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('success')
                ->chart([1, 3, 5, 2, 8, 12, $publishedVehicles]),

            Stat::make(__('widgets.avg_daily_rate'), 'RM '.number_format($averageRate, 2))
                ->description(__('widgets.average_rental_rate'))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('info')
                ->chart([45, 52, 48, 61, 58, 63, $averageRate]),
        ];
    }
}
