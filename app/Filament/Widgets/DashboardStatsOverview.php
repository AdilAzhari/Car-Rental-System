<?php

namespace App\Filament\Widgets;

use App\Helpers\CurrencyHelper;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Generate stats based on user role
        if ($user->role === 'admin') {
            return $this->getAdminStats($currentMonth, $lastMonth);
        } elseif ($user->role === 'owner') {
            return $this->getOwnerStats($user, $currentMonth, $lastMonth);
        } else {
            return $this->getRenterStats($user, $currentMonth);
        }
    }

    private function getAdminStats($currentMonth, $lastMonth): array
    {
        // Users statistics
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', $currentMonth)->count();
        $newUsersLastMonth = User::whereBetween('created_at', [$lastMonth, $currentMonth])->count();
        $userGrowth = $newUsersLastMonth > 0 ? (($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100 : 0;

        // Vehicles statistics
        Vehicle::count();
        $publishedVehicles = Vehicle::where('status', 'published')->count();
        $availableVehicles = Vehicle::where('status', 'published')->where('is_available', true)->count();

        // Bookings statistics
        Booking::count();
        $activeBookings = Booking::where('status', 'ongoing')->count();
        Booking::where('status', 'completed')->count();
        $bookingsThisMonth = Booking::where('created_at', '>=', $currentMonth)->count();

        // Revenue statistics
        Payment::where('payment_status', 'confirmed')->sum('amount');
        $monthlyRevenue = Payment::where('payment_status', 'confirmed')
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');
        $lastMonthRevenue = Payment::where('payment_status', 'confirmed')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // Reviews statistics
        Review::count();
        $averageRating = Review::where('is_visible', true)->avg('rating');
        $reviewsThisMonth = Review::where('created_at', '>=', $currentMonth)->count();

        // Generate chart data for the last 7 days
        $last7Days = collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'))->reverse();

        $bookingChart = $last7Days->map(fn ($date) => Booking::whereDate('created_at', $date)->count())->toArray();

        $revenueChart = $last7Days->map(fn ($date) => Payment::where('payment_status', 'confirmed')
            ->whereDate('created_at', $date)
            ->sum('amount'))->toArray();

        return [
            Stat::make(__('widgets.total_users'), number_format($totalUsers))
                ->description($userGrowth >= 0 ? "+{$userGrowth}% ".__('widgets.growth_from_last_month') : "{$userGrowth}% ".__('widgets.growth_from_last_month'))
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart($last7Days->map(fn ($date) => User::whereDate('created_at', $date)->count())->toArray()),

            Stat::make(__('widgets.available_vehicles'), number_format($availableVehicles).' / '.number_format($publishedVehicles))
                ->description(__('widgets.ready_for_rent'))
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->chart($last7Days->map(fn ($date) => Vehicle::whereDate('created_at', $date)->count())->toArray()),

            Stat::make(__('widgets.active_bookings'), number_format($activeBookings))
                ->description($bookingsThisMonth.' '.__('widgets.bookings').' '.__('widgets.this_month'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart($bookingChart),

            Stat::make(__('widgets.monthly_revenue'), CurrencyHelper::format($monthlyRevenue))
                ->description($revenueGrowth >= 0 ? "+{$revenueGrowth}% ".__('widgets.growth_from_last_month') : "{$revenueGrowth}% ".__('widgets.growth_from_last_month'))
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'warning')
                ->chart($revenueChart),

            Stat::make(__('resources.rating'), number_format($averageRating ?: 0, 1).'/5 ⭐')
                ->description($reviewsThisMonth.' '.__('resources.reviews').' '.__('widgets.this_month'))
                ->descriptionIcon('heroicon-m-star')
                ->color($averageRating >= 4 ? 'success' : ($averageRating >= 3 ? 'warning' : 'danger'))
                ->chart($last7Days->map(fn ($date) => Review::whereDate('created_at', $date)->count())->toArray()),
        ];
    }

    private function getOwnerStats($user, $currentMonth, $lastMonth): array
    {
        // Owner's vehicles statistics
        $myVehicles = Vehicle::where('owner_id', $user->id)->count();
        $publishedVehicles = Vehicle::where('owner_id', $user->id)->where('status', 'published')->count();
        $availableVehicles = Vehicle::where('owner_id', $user->id)->where('status', 'published')->where('is_available', true)->count();

        // Bookings for owner's vehicles
        $myBookings = Booking::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->count();
        $activeBookings = Booking::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('status', 'ongoing')->count();
        $completedBookings = Booking::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('status', 'completed')->count();
        $bookingsThisMonth = Booking::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('created_at', '>=', $currentMonth)->count();

        // Revenue from owner's vehicles
        Payment::whereHas('booking.vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('payment_status', 'confirmed')->sum('amount');
        $monthlyRevenue = Payment::whereHas('booking.vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('payment_status', 'confirmed')->where('created_at', '>=', $currentMonth)->sum('amount');
        $lastMonthRevenue = Payment::whereHas('booking.vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('payment_status', 'confirmed')->whereBetween('created_at', [$lastMonth, $currentMonth])->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // Reviews for owner's vehicles
        $myReviews = Review::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->count();
        $averageRating = Review::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('is_visible', true)->avg('rating');
        $reviewsThisMonth = Review::whereHas('vehicle', fn ($q) => $q->where('owner_id', $user->id))->where('created_at', '>=', $currentMonth)->count();

        collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'))->reverse();

        return [
            Stat::make(__('widgets.my_vehicles'), number_format($myVehicles))
                ->description($publishedVehicles.' '.__('widgets.published_comma_available').' '.$availableVehicles.' '.__('widgets.available_vehicles'))
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make(__('widgets.total_bookings'), number_format($myBookings))
                ->description($bookingsThisMonth.' '.__('widgets.bookings_this_month'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make(__('widgets.active_bookings'), number_format($activeBookings))
                ->description($completedBookings.' '.__('widgets.completed_bookings'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make(__('widgets.monthly_revenue'), CurrencyHelper::format($monthlyRevenue))
                ->description($revenueGrowth >= 0 ? "+{$revenueGrowth}% ".__('widgets.from_last_month') : "{$revenueGrowth}% ".__('widgets.from_last_month'))
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger'),

            Stat::make(__('widgets.average_rating'), number_format($averageRating ?: 0, 1).'/5 ⭐')
                ->description($myReviews.' '.__('widgets.total_reviews').', '.$reviewsThisMonth.' '.__('widgets.this_month'))
                ->descriptionIcon('heroicon-m-star')
                ->color($averageRating >= 4 ? 'success' : ($averageRating >= 3 ? 'warning' : 'danger')),
        ];
    }

    private function getRenterStats($user, $currentMonth): array
    {
        // Renter's bookings statistics
        $myBookings = Booking::where('renter_id', $user->id)->count();
        $activeBookings = Booking::where('renter_id', $user->id)->where('status', 'ongoing')->count();
        $completedBookings = Booking::where('renter_id', $user->id)->where('status', 'completed')->count();
        $pendingBookings = Booking::where('renter_id', $user->id)->where('status', 'pending')->count();
        $bookingsThisMonth = Booking::where('renter_id', $user->id)->where('created_at', '>=', $currentMonth)->count();

        // Renter's spending
        $totalSpent = Payment::whereHas('booking', fn ($q) => $q->where('renter_id', $user->id))->where('payment_status', 'confirmed')->sum('amount');
        $monthlySpent = Payment::whereHas('booking', fn ($q) => $q->where('renter_id', $user->id))->where('payment_status', 'confirmed')->where('created_at', '>=', $currentMonth)->sum('amount');

        // Renter's reviews
        $myReviews = Review::where('renter_id', $user->id)->count();
        $reviewsThisMonth = Review::where('renter_id', $user->id)->where('created_at', '>=', $currentMonth)->count();

        // Available vehicles for renting
        $availableVehicles = Vehicle::where('status', 'published')->where('is_available', true)->count();

        return [
            Stat::make(__('widgets.my_bookings'), number_format($myBookings))
                ->description($bookingsThisMonth.' '.__('widgets.bookings_this_month'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make(__('widgets.active_bookings'), number_format($activeBookings))
                ->description($pendingBookings.' '.__('widgets.pending').', '.$completedBookings.' '.__('widgets.completed'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make(__('widgets.available_vehicles'), number_format($availableVehicles))
                ->description(__('widgets.ready_for_booking'))
                ->descriptionIcon('heroicon-m-truck')
                ->color('success'),

            Stat::make(__('widgets.monthly_spending'), CurrencyHelper::format($monthlySpent))
                ->description(CurrencyHelper::format($totalSpent).' '.__('widgets.total_spent'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make(__('widgets.my_reviews'), number_format($myReviews))
                ->description($reviewsThisMonth.' '.__('widgets.reviews_this_month'))
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
}
