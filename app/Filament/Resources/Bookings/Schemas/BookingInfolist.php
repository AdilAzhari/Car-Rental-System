<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Exception;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class BookingInfolist
{
    /**
     * @throws Exception
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Booking Overview Section
                Section::make('Booking Overview')
                    ->description('Complete information about this booking')
                    ->icon('heroicon-m-calendar')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Booking ID')
                                    ->icon('heroicon-m-hashtag')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->color('primary'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->icon('heroicon-m-flag')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => match (true) {
                                        $state instanceof BookingStatus => $state->label(),
                                        is_string($state) => BookingStatus::from($state)->label(),
                                        default => 'Unknown'
                                    })
                                    ->color(fn ($state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                                        BookingStatus::PENDING => 'warning',
                                        BookingStatus::CONFIRMED => 'info',
                                        BookingStatus::ONGOING => 'primary',
                                        BookingStatus::COMPLETED => 'success',
                                        BookingStatus::CANCELLED => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('payment_status')
                                    ->label('Payment Status')
                                    ->icon('heroicon-m-currency-dollar')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => match (true) {
                                        $state instanceof PaymentStatus => $state->label(),
                                        is_string($state) => PaymentStatus::from($state)->label(),
                                        default => 'Unknown'
                                    })
                                    ->color(fn ($state): string => match ($state instanceof PaymentStatus ? $state : PaymentStatus::from($state)) {
                                        PaymentStatus::PENDING => 'warning',
                                        PaymentStatus::CONFIRMED => 'success',
                                        PaymentStatus::FAILED => 'danger',
                                        PaymentStatus::REFUNDED => 'info',
                                        PaymentStatus::CANCELLED => 'secondary',
                                        PaymentStatus::PROCESSING => 'primary',
                                        PaymentStatus::UNPAID => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->icon('heroicon-m-currency-dollar')
                                    ->money('MYR')
                                    ->size('lg')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),
                            ]),
                    ]),

                // Customer & Vehicle Section
                Section::make('Customer & Vehicle Details')
                    ->description('Information about the renter and vehicle')
                    ->icon('heroicon-m-user-group')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextEntry::make('renter.name')
                                    ->label('Renter')
                                    ->icon('heroicon-m-user')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                TextEntry::make('renter.email')
                                    ->label('Renter Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!'),
                            ]),

                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextEntry::make('vehicle.make')
                                    ->label('Vehicle')
                                    ->formatStateUsing(fn ($record) => $record->vehicle ? $record->vehicle->make . ' ' . $record->vehicle->model . ' (' . $record->vehicle->year . ')' : 'N/A')
                                    ->icon('heroicon-m-truck')
                                    ->weight(FontWeight::Bold)
                                    ->color('info'),

                                TextEntry::make('vehicle.plate_number')
                                    ->label('License Plate')
                                    ->icon('heroicon-m-rectangle-group')
                                    ->copyable()
                                    ->copyMessage('Plate number copied!')
                                    ->weight(FontWeight::Bold),
                            ]),
                    ]),

                // Booking Timeline Section
                Section::make('Booking Timeline')
                    ->description('Important dates and duration')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextEntry::make('start_date')
                                    ->label('Start Date & Time')
                                    ->icon('heroicon-m-play')
                                    ->dateTime()
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),

                                TextEntry::make('end_date')
                                    ->label('End Date & Time')
                                    ->icon('heroicon-m-stop')
                                    ->dateTime()
                                    ->weight(FontWeight::Bold)
                                    ->color('danger'),
                            ]),

                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Booking Created')
                                    ->icon('heroicon-m-plus-circle')
                                    ->dateTime()
                                    ->since()
                                    ->dateTimeTooltip(),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->icon('heroicon-m-pencil')
                                    ->dateTime()
                                    ->since()
                                    ->dateTimeTooltip(),
                            ]),
                    ]),

                // Financial Details Section
                Section::make('Financial Details')
                    ->description('Payment and financial information')
                    ->icon('heroicon-m-banknotes')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->icon('heroicon-m-currency-dollar')
                                    ->money('MYR')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),

                                TextEntry::make('deposit_amount')
                                    ->label('Deposit')
                                    ->icon('heroicon-m-shield-check')
                                    ->money('MYR')
                                    ->placeholder('No deposit required'),

                                TextEntry::make('commission_amount')
                                    ->label('Commission')
                                    ->icon('heroicon-m-percent-badge')
                                    ->money('MYR')
                                    ->placeholder('No commission'),
                            ]),

                        TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->icon('heroicon-m-credit-card')
                            ->placeholder('Not specified')
                            ->badge()
                            ->color('info'),
                    ]),

                // Location & Special Requests Section
                Section::make('Additional Information')
                    ->description('Pickup, dropoff, and special requests')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextEntry::make('pickup_location')
                                    ->label('Pickup Location')
                                    ->icon('heroicon-m-map-pin')
                                    ->placeholder('Not specified'),

                                TextEntry::make('dropoff_location')
                                    ->label('Dropoff Location')
                                    ->icon('heroicon-m-flag')
                                    ->placeholder('Not specified'),
                            ]),

                        TextEntry::make('special_requests')
                            ->label('Special Requests')
                            ->icon('heroicon-m-chat-bubble-left-ellipsis')
                            ->placeholder('No special requests')
                            ->html()
                            ->columnSpanFull(),
                    ]),

                // Quick Actions Section
                Section::make('Quick Actions')
                    ->schema([
                        Actions::make([
                            Action::make('edit')
                                ->label('Edit Booking')
                                ->icon('heroicon-m-pencil-square')
                                ->url(fn ($record): string => route('filament.admin.resources.bookings.edit', $record))
                                ->color('primary')
                                ->button(),

                            Action::make('view_payment')
                                ->label('View Payment')
                                ->icon('heroicon-m-currency-dollar')
                                ->color('success')
                                ->button()
                                ->visible(fn ($record) => $record->payment()->exists())
                                ->url(fn ($record): string => route('filament.admin.resources.bookings.view', ['record' => $record, 'tab' => 'payment-relation-manager'])),
                        ]),
                    ])
                    ->compact(),
            ]);
    }
}