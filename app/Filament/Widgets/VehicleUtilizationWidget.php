<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Widgets\ChartWidget;

class VehicleUtilizationWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return __('widgets.vehicle_status_distribution');
    }

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->role === UserRole::ADMIN || $user->role === UserRole::OWNER);
    }

    protected function getData(): array
    {
        $user = auth()->user();

        // Base query - filter by owner if not admin
        $baseQuery = Vehicle::query();
        if ($user && $user->role === UserRole::OWNER) {
            $baseQuery->where('owner_id', $user->id);
        }

        $pending = (clone $baseQuery)->where('status', VehicleStatus::PENDING)->count();
        $published = (clone $baseQuery)->where('status', VehicleStatus::PUBLISHED)->count();
        $suspended = (clone $baseQuery)->where('status', VehicleStatus::REJECTED)->count();
        $maintenance = (clone $baseQuery)->where('status', VehicleStatus::MAINTENANCE)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Vehicle Status',
                    'data' => [$published, $pending, $suspended, $maintenance],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',   // Green for Published
                        'rgba(251, 191, 36, 0.8)',  // Yellow for Pending
                        'rgba(239, 68, 68, 0.8)',   // Red for Suspended
                        'rgba(156, 163, 175, 0.8)', // Gray for Maintenance
                    ],
                    'borderColor' => [
                        'rgba(34, 197, 94, 1)',
                        'rgba(251, 191, 36, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(156, 163, 175, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Published', 'Pending', 'Suspended', 'Maintenance'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.label || "";
                            if (label) {
                                label += ": ";
                            }
                            label += context.parsed + " vehicles";
                            return label;
                        }',
                    ],
                ],
            ],
        ];
    }
}
