<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('Add Review'),
        ];
    }

    public function getTabs(): array
    {
        $modelClass = $this->getResource()::getModel();

        // Calculate counts eagerly to avoid model context issues
        $allCount = $modelClass::count();
        $visibleCount = $modelClass::where('is_visible', true)->count();
        $hiddenCount = $modelClass::where('is_visible', false)->count();
        $highRatingCount = $modelClass::where('rating', 5)->count();
        $goodRatingCount = $modelClass::where('rating', '>=', 4)->count();
        $lowRatingCount = $modelClass::where('rating', '<=', 2)->count();
        $recentCount = $modelClass::where('created_at', '>=', now()->subDays(7))->count();

        return [
            'all' => Tab::make('All Reviews')
                ->badge($allCount),

            'visible' => Tab::make('Visible')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('is_visible', true))
                ->badge($visibleCount)
                ->icon('heroicon-m-eye'),

            'hidden' => Tab::make('Hidden')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('is_visible', false))
                ->badge($hiddenCount)
                ->icon('heroicon-m-eye-slash'),

            'high_rating' => Tab::make('5 Stars')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('rating', 5))
                ->badge($highRatingCount)
                ->icon('heroicon-m-star'),

            'good_rating' => Tab::make('4+ Stars')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('rating', '>=', 4))
                ->badge($goodRatingCount)
                ->icon('heroicon-m-hand-thumb-up'),

            'low_rating' => Tab::make('Low Ratings')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('rating', '<=', 2))
                ->badge($lowRatingCount)
                ->icon('heroicon-m-exclamation-triangle'),

            'recent' => Tab::make('Recent (7 days)')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('created_at', '>=', now()->subDays(7)))
                ->badge($recentCount)
                ->icon('heroicon-m-calendar-days'),
        ];
    }
}
