<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularVehiclesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Vehicle::query()
                    ->withCount('bookings')
                    ->withAvg('reviews', 'rating')
                    ->where('status', 'published')
                    ->orderBy('bookings_count', 'desc')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label(__('widgets.image'))
                    ->circular()
                    ->defaultImageUrl(url('/images/car-placeholder.jpg'))
                    ->size(50),

                Tables\Columns\TextColumn::make('make')
                    ->label(__('widgets.vehicle'))
                    ->formatStateUsing(fn ($record): string => "{$record->make} {$record->model} ({$record->year})")
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\BadgeColumn::make('category')
                    ->label(__('widgets.category'))
                    ->colors([
                        'success' => 'economy',
                        'info' => 'compact',
                        'warning' => 'midsize',
                        'primary' => 'fullsize',
                        'danger' => 'luxury',
                        'gray' => 'suv',
                    ]),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label(__('widgets.bookings'))
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviews_avg_rating')
                    ->label(__('widgets.rating'))
                    ->formatStateUsing(fn ($state): string|array|null => $state ? number_format($state, 1).'/5 ⭐' : __('widgets.no_ratings'))
                    ->color('warning'),

                Tables\Columns\TextColumn::make('daily_rate')
                    ->label(__('widgets.daily_rate'))
                    ->money('MYR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label(__('widgets.revenue'))
                    ->state(fn($record) => $record->bookings()
                        ->where('status', 'completed')
                        ->sum('total_amount'))
                    ->money('MYR')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('widgets.status'))
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'maintenance',
                        'gray' => 'archived',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('widgets.view'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (Vehicle $record): string => route('filament.admin.resources.vehicles.view', $record)
                    ),

                Action::make('book')
                    ->label(__('widgets.book'))
//                    ->icon('heroicon-m-calendar-plus')
                    ->color('success')
                    ->url(fn (Vehicle $record): string => route('filament.admin.resources.bookings.create', ['vehicle_id' => $record->id])
                    ),
            ])
            ->heading(__('widgets.popular_vehicles'))
            ->description(__('widgets.top_performing_vehicles'));
    }
}
