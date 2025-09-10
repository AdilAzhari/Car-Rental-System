<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get current period data
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        
        // Users statistics
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', $currentMonth)->count();
        $newUsersLastMonth = User::whereBetween('created_at', [$lastMonth, $currentMonth])->count();
        $userGrowth = $newUsersLastMonth > 0 ? (($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100 : 0;

        // Vehicles statistics
        $totalVehicles = Vehicle::count();
        $publishedVehicles = Vehicle::where('status', 'published')->count();
        $availableVehicles = Vehicle::where('status', 'published')->where('is_available', true)->count();

        // Bookings statistics
        $totalBookings = Booking::count();
        $activeBookings = Booking::where('status', 'active')->count();
        $completedBookings = Booking::where('status', 'completed')->count();
        $bookingsThisMonth = Booking::where('created_at', '>=', $currentMonth)->count();

        // Revenue statistics
        $totalRevenue = Payment::where('payment_status', 'confirmed')->sum('amount');
        $monthlyRevenue = Payment::where('payment_status', 'confirmed')
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');
        $lastMonthRevenue = Payment::where('payment_status', 'confirmed')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // Reviews statistics
        $totalReviews = Review::count();
        $averageRating = Review::where('status', 'approved')->avg('rating');
        $reviewsThisMonth = Review::where('created_at', '>=', $currentMonth)->count();

        // Generate chart data for the last 7 days
        $last7Days = collect(range(0, 6))->map(function ($i) {
            return now()->subDays($i)->format('Y-m-d');
        })->reverse();

        $bookingChart = $last7Days->map(function ($date) {
            return Booking::whereDate('created_at', $date)->count();
        })->toArray();

        $revenueChart = $last7Days->map(function ($date) {
            return Payment::where('payment_status', 'confirmed')
                ->whereDate('created_at', $date)
                ->sum('amount');
        })->toArray();

        return [
            Stat::make(__('widgets.total_users'), number_format($totalUsers))
                ->description($userGrowth >= 0 ? "+{$userGrowth}% " . __('widgets.growth_from_last_month') : "{$userGrowth}% " . __('widgets.growth_from_last_month'))
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart($last7Days->map(fn($date) => User::whereDate('created_at', $date)->count())->toArray()),

            Stat::make(__('widgets.available_vehicles'), number_format($availableVehicles) . ' / ' . number_format($publishedVehicles))
                ->description(__('widgets.ready_for_rent'))
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->chart($last7Days->map(fn($date) => Vehicle::whereDate('created_at', $date)->count())->toArray()),

            Stat::make(__('widgets.active_bookings'), number_format($activeBookings))
                ->description($bookingsThisMonth . ' ' . __('widgets.bookings') . ' ' . __('widgets.this_month'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart($bookingChart),

            Stat::make(__('widgets.monthly_revenue'), 'RM ' . number_format($monthlyRevenue, 2))
                ->description($revenueGrowth >= 0 ? "+{$revenueGrowth}% " . __('widgets.growth_from_last_month') : "{$revenueGrowth}% " . __('widgets.growth_from_last_month'))
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'warning')
                ->chart($revenueChart),

            Stat::make(__('resources.rating'), number_format($averageRating, 1) . '/5 ⭐')
                ->description($reviewsThisMonth . ' ' . __('resources.reviews') . ' ' . __('widgets.this_month'))
                ->descriptionIcon('heroicon-m-star')
                ->color($averageRating >= 4 ? 'success' : ($averageRating >= 3 ? 'warning' : 'danger'))
                ->chart($last7Days->map(fn($date) => Review::whereDate('created_at', $date)->count())->toArray()),
        ];
    }
}