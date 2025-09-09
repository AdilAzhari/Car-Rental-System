<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularVehiclesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

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
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/car-placeholder.jpg'))
                    ->size(50),

                Tables\Columns\TextColumn::make('make')
                    ->label('Vehicle')
                    ->formatStateUsing(fn ($record) => "{$record->make} {$record->model} ({$record->year})")
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Category')
                    ->colors([
                        'success' => 'economy',
                        'info' => 'compact',
                        'warning' => 'midsize',
                        'primary' => 'fullsize',
                        'danger' => 'luxury',
                        'gray' => 'suv',
                    ]),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviews_avg_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/5 ⭐' : 'No ratings')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->money('MYR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->state(function ($record) {
                        return $record->bookings()
                            ->where('status', 'completed')
                            ->sum('total_amount');
                    })
                    ->money('MYR')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'maintenance',
                        'gray' => 'archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Vehicle $record): string => 
                        route('filament.admin.resources.vehicles.view', $record)
                    ),

                Tables\Actions\Action::make('book')
                    ->label('Book')
                    ->icon('heroicon-m-calendar-plus')
                    ->color('success')
                    ->url(fn (Vehicle $record): string => 
                        route('filament.admin.resources.bookings.create', ['vehicle_id' => $record->id])
                    ),
            ])
            ->heading('Most Popular Vehicles')
            ->description('Top performing vehicles by booking count and revenue');
    }
}