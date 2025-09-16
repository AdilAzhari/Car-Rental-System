<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReviewStatsWidget extends BaseWidget
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
        $baseQuery = Review::query();
        if ($user && $user->role === UserRole::OWNER) {
            $baseQuery->whereHas('booking.vehicle', function ($query) use ($user): void {
                $query->where('owner_id', $user->id);
            });
        }

        // Total reviews
        $totalReviews = $baseQuery->count();

        // Average rating
        $averageRating = $baseQuery->avg('rating') ?? 0;

        // Rating distribution
        $fiveStars = (clone $baseQuery)->where('rating', 5)->count();
        $fourStars = (clone $baseQuery)->where('rating', 4)->count();
        $threeStars = (clone $baseQuery)->where('rating', 3)->count();
        $twoStars = (clone $baseQuery)->where('rating', 2)->count();
        $oneStar = (clone $baseQuery)->where('rating', 1)->count();

        // Recent reviews (last 30 days)
        $recentReviews = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Reviews with comments
        $reviewsWithComments = (clone $baseQuery)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->count();

        // Review trend for the last 7 days
        $reviewTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = (clone $baseQuery)
                ->whereDate('created_at', $date)
                ->count();
            $reviewTrend[] = $count;
        }

        // Rating trend for the last 7 days
        $ratingTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $avgRating = (clone $baseQuery)
                ->whereDate('created_at', $date)
                ->avg('rating') ?? 0;
            $ratingTrend[] = max(1, $avgRating);
        }

        return [
            Stat::make(__('widgets.total_reviews'), $totalReviews)
                ->description(__('widgets.all_customer_reviews'))
                ->descriptionIcon('heroicon-o-star')
                ->color('primary')
                ->chart($reviewTrend),

            Stat::make(__('widgets.average_rating'), number_format($averageRating, 1).'/5')
                ->description(__('widgets.overall_satisfaction'))
                ->descriptionIcon('heroicon-o-heart')
                ->color($averageRating >= 4.5 ? 'success' : ($averageRating >= 3.5 ? 'warning' : 'danger'))
                ->chart($ratingTrend),

            Stat::make(__('widgets.5-star_reviews'), $fiveStars)
                ->description(__('widgets.excellent_ratings'))
                ->descriptionIcon('heroicon-o-star')
                ->color('success')
                ->chart([
                    max(1, $fiveStars * 0.2),
                    max(1, $fiveStars * 0.4),
                    max(1, $fiveStars * 0.6),
                    max(1, $fiveStars * 0.8),
                    max(1, $fiveStars),
                ]),

            Stat::make(__('widgets.recent_reviews'), $recentReviews)
                ->description(__('widgets.last_30_days'))
                ->descriptionIcon('heroicon-o-clock')
                ->color($recentReviews > 5 ? 'success' : 'warning')
                ->chart(array_slice($reviewTrend, -5)),

            Stat::make(__('widgets.with_comments'), $reviewsWithComments)
                ->description(__('widgets.detailed_feedback'))
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->chart([
                    max(1, $reviewsWithComments * 0.3),
                    max(1, $reviewsWithComments * 0.6),
                    max(1, $reviewsWithComments * 0.8),
                    max(1, $reviewsWithComments),
                ]),

            Stat::make(__('widgets.rating_distribution'), $fiveStars.'/'.$fourStars.'/'.$threeStars.'/'.$twoStars.'/'.$oneStar)
                ->description('5★/4★/3★/2★/1★')
                ->descriptionIcon('heroicon-o-chart-bar-square')
                ->color('info')
                ->chart([
                    max(1, $fiveStars),
                    max(1, $fourStars),
                    max(1, $threeStars),
                    max(1, $twoStars),
                    max(1, $oneStar),
                ]),
        ];
    }
}
