<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsWidget extends BaseWidget
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

        // Base query - filter by owner's vehicles if not admin
        $baseQuery = Booking::query();
        if ($user && $user->role === UserRole::OWNER) {
            $baseQuery->whereHas('vehicle', function ($query) use ($user): void {
                $query->where('owner_id', $user->id);
            });
        }

        // Total bookings
        $totalBookings = $baseQuery->count();

        // Pending bookings
        $pendingBookings = (clone $baseQuery)->where('status', BookingStatus::PENDING)->count();

        // Confirmed bookings
        $confirmedBookings = (clone $baseQuery)->where('status', BookingStatus::CONFIRMED)->count();

        // This month's bookings
        $thisMonthBookings = (clone $baseQuery)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        // Revenue from completed bookings
        $totalRevenue = (clone $baseQuery)
            ->where('status', BookingStatus::COMPLETED)
            ->sum('total_amount');

        // Average booking duration
        $averageDuration = (clone $baseQuery)
            ->where('status', BookingStatus::COMPLETED)
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->value('avg_duration') ?? 0;

        // Booking trend for the last 7 days
        $bookingTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = (clone $baseQuery)
                ->whereDate('created_at', $date)
                ->count();
            $bookingTrend[] = $count;
        }

        return [
            Stat::make('Total Bookings', $totalBookings)
                ->description('All time bookings')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary')
                ->chart($bookingTrend),

            Stat::make('Pending', $pendingBookings)
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingBookings > 0 ? 'warning' : 'success')
                ->chart(array_slice($bookingTrend, -4)),

            Stat::make('Confirmed', $confirmedBookings)
                ->description('Active reservations')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info')
                ->chart(array_slice($bookingTrend, -4)),

            Stat::make('This Month', $thisMonthBookings)
                ->description('Current month bookings')
                ->descriptionIcon('heroicon-o-chart-bar-square')
                ->color('success')
                ->chart(array_slice($bookingTrend, -5)),

            Stat::make('Total Revenue', 'RM '.number_format($totalRevenue, 2))
                ->description('From completed bookings')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->chart([
                    max(1, $totalRevenue * 0.1),
                    max(1, $totalRevenue * 0.3),
                    max(1, $totalRevenue * 0.5),
                    max(1, $totalRevenue * 0.7),
                    max(1, $totalRevenue * 0.9),
                    max(1, $totalRevenue),
                ]),

            Stat::make('Avg. Duration', round($averageDuration, 1).' days')
                ->description('Average rental period')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info')
                ->chart([2, 4, 3, 5, 4, max(1, round($averageDuration))]),
        ];
    }
}
