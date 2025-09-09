<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
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
            'all' => Tab::make('All Reviews')
                ->badge(fn () => $this->getResource()::getModel()::count()),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'pending')->count())
                ->icon('heroicon-m-clock'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'approved')->count())
                ->icon('heroicon-m-check-circle'),

            'high_rating' => Tab::make('5 Stars')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', 5)->where('status', 'approved'))
                ->badge(fn () => $this->getResource()::getModel()::where('rating', 5)->where('status', 'approved')->count())
                ->icon('heroicon-m-star'),

            'low_rating' => Tab::make('Low Ratings')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('rating', '<=', 2))
                ->badge(fn () => $this->getResource()::getModel()::where('rating', '<=', 2)->count())
                ->icon('heroicon-m-exclamation-triangle'),

            'flagged' => Tab::make('Flagged')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'flagged'))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'flagged')->count())
                ->icon('heroicon-m-flag'),

            'recent' => Tab::make('Recent (7 days)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7)))
                ->badge(fn () => $this->getResource()::getModel()::where('created_at', '>=', now()->subDays(7))->count())
                ->icon('heroicon-m-calendar-days'),
        ];
    }
}