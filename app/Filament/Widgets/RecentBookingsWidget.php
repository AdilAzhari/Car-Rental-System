<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Helpers\CurrencyHelper;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::query()->with(['renter', 'vehicle'])->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('widgets.booking_number'))
                    ->prefix('BK-')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('renter.name')
                    ->label(__('widgets.customer'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle')
                    ->label(__('widgets.vehicle'))
                    ->formatStateUsing(fn ($record): string => "{$record->vehicle->make} {$record->vehicle->model}")
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('widgets.start_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('widgets.end_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label(__('widgets.duration'))
                    ->state(function ($record): string {
                        $days = \Carbon\Carbon::parse($record->start_date)
                            ->diffInDays(\Carbon\Carbon::parse($record->end_date)) + 1;

                        return $days.' '.($days !== 1 ? __('widgets.days') : __('widgets.day'));
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('widgets.status'))
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => __('widgets.status_' . $state)),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('widgets.amount'))
                    ->money(config('app.currency', 'MYR'))
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('widgets.booked'))
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('widgets.view'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (Booking $record): string => route('filament.admin.resources.bookings.view', $record)
                    ),

                Action::make('edit')
                    ->label(__('widgets.edit'))
                    ->icon('heroicon-m-pencil')
                    ->color('warning')
                    ->url(fn (Booking $record): string => route('filament.admin.resources.bookings.edit', $record)
                    ),

                Action::make('confirm')
                    ->label(__('widgets.confirm'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->status === 'pending')
                    ->action(function (Booking $record): void {
                        $record->update(['status' => 'confirmed']);

                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log(__('widgets.booking_confirmed_via_dashboard'));
                    })
                    ->requiresConfirmation(),
            ])
            ->heading(__('widgets.recent_bookings'))
            ->description(__('widgets.latest_bookings_activity'))
            ->poll('60s');
    }
}
