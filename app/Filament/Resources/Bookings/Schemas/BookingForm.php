<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\UserRole;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Booking Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('renter_id')
                                    ->label(__('resources.renter'))
                                    ->relationship('renter', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->visible(fn (): bool => auth()->user()->role !== UserRole::RENTER),

                                Select::make('vehicle_id')
                                    ->label(__('resources.vehicle'))
                                    ->relationship('vehicle', modifyQueryUsing: fn ($query) => $query->when(auth()->user()->role === UserRole::RENTER, fn ($q) => $q->where('status', 'published')->where('is_available', true)
                                    )
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => "$record->make $record->model ($record->plate_number)")
                                    ->searchable(['make', 'model', 'plate_number'])
                                    ->preload()
                                    ->required(),
                            ]),

                        Grid::make()
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label(__('resources.start_date'))
                                    ->required()
                                    ->native(false),

                                DatePicker::make('end_date')
                                    ->label(__('resources.end_date'))
                                    ->required()
                                    ->native(false)
                                    ->after('start_date'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('days')
                                    ->label(__('resources.number_of_days'))
                                    ->numeric()
                                    ->readonly(),

                                TextInput::make('daily_rate')
                                    ->label(__('resources.daily_rate'))
                                    ->numeric()
                                    ->prefix('$')
                                    ->readonly(),

                                TextInput::make('subtotal')
                                    ->label(__('resources.subtotal'))
                                    ->numeric()
                                    ->prefix('$')
                                    ->readonly(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('insurance_fee')
                                    ->label(__('resources.insurance_fee'))
                                    ->numeric()
                                    ->prefix('$')
                                    ->readonly(),

                                TextInput::make('tax_amount')
                                    ->label(__('resources.tax_amount'))
                                    ->numeric()
                                    ->prefix('$')
                                    ->readonly(),

                                TextInput::make('total_amount')
                                    ->label(__('resources.total_amount'))
                                    ->numeric()
                                    ->prefix('$')
                                    ->readonly(),
                            ]),
                    ]),

                Section::make('Status & Location')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('status')
                                    ->label(__('resources.booking_status'))
                                    ->options([
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'ongoing' => 'Ongoing',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required(),

                                Select::make('payment_status')
                                    ->label(__('resources.payment_status'))
                                    ->options([
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'processing' => 'Processing',
                                        'failed' => 'Failed',
                                        'refunded' => 'Refunded',
                                        'cancelled' => 'Cancelled',
                                        'unpaid' => 'Unpaid',
                                    ])
                                    ->required(),
                            ]),

                        TextInput::make('pickup_location')
                            ->label(__('resources.pickup_location'))
                            ->maxLength(255),

                        Textarea::make('special_requests')
                            ->label(__('resources.special_requests'))
                            ->rows(3)
                            ->maxLength(500),
                    ]),
            ]);
    }
}
