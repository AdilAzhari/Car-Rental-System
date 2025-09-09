<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Enums\BookingStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::query()->with(['renter', 'vehicle'])->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Booking #')
                    ->prefix('BK-')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('renter.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle')
                    ->label('Vehicle')
                    ->formatStateUsing(fn ($record) => "{$record->vehicle->make} {$record->vehicle->model}")
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record) {
                        $days = \Carbon\Carbon::parse($record->start_date)
                            ->diffInDays(\Carbon\Carbon::parse($record->end_date)) + 1;
                        return $days . ' day' . ($days !== 1 ? 's' : '');
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => BookingStatus::tryFrom($state)?->label() ?? $state),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Booking $record): string => 
                        route('filament.admin.resources.bookings.view', $record)
                    ),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->color('warning')
                    ->url(fn (Booking $record): string => 
                        route('filament.admin.resources.bookings.edit', $record)
                    ),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Booking $record) => $record->status === 'pending')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'confirmed']);
                        
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('Booking confirmed via dashboard widget');
                    })
                    ->requiresConfirmation(),
            ])
            ->heading('Recent Bookings')
            ->description('Latest bookings and their current status')
            ->poll('60s');
    }
}