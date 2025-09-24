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
        return [
            'all' => Tab::make(__('resources.all_reviews'))
                ->badge(fn () => $this->getResource()::getModel()::count()),

            'visible' => Tab::make(__('resources.visible'))
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('is_visible', true))
                ->badge(fn () => $this->getResource()::getModel()::where('is_visible', true)->count())
                ->icon('heroicon-m-eye'),

            'hidden' => Tab::make(__('resources.hidden'))
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('is_visible', false))
                ->badge(fn () => $this->getResource()::getModel()::where('is_visible', false)->count())
                ->icon('heroicon-m-eye-slash'),

            'high_rating' => Tab::make(__('resources.5_stars'))
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('rating', 5))
                ->badge(fn () => $this->getResource()::getModel()::where('rating', 5)->count())
                ->icon('heroicon-m-star'),

            'good_rating' => Tab::make(__('resources.4_plus_stars'))
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('rating', '>=', 4))
                ->badge(fn () => $this->getResource()::getModel()::where('rating', '>=', 4)->count())
                ->icon('heroicon-m-hand-thumb-up'),

            'low_rating' => Tab::make(__('resources.low_ratings'))
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('rating', '<=', 2))
                ->badge(fn () => $this->getResource()::getModel()::where('rating', '<=', 2)->count())
                ->icon('heroicon-m-exclamation-triangle'),

            'recent' => Tab::make(__('resources.recent_7_days'))
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('created_at', '>=', now()->subDays(7)))
                ->badge(fn () => $this->getResource()::getModel()::where('created_at', '>=', now()->subDays(7))->count())
                ->icon('heroicon-m-calendar-days'),
        ];
    }
}
