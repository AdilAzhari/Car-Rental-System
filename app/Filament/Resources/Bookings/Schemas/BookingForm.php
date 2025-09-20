<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Carbon\Carbon;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.booking_information'))
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
                                    ->relationship('vehicle', modifyQueryUsing: fn ($query) => $query->when(auth()->user()->role === UserRole::RENTER, fn ($q)
                                            => $q->where('status', 'published')->where('is_available', true)
                                        )
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => "$record->make $record->model ($record->plate_number)")
                                    ->searchable(['make', 'model', 'plate_number'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if ($state) {
                                            $vehicle = Vehicle::query()->find($state);
                                            if ($vehicle) {
                                                $set('daily_rate', $vehicle->daily_rate);
                                                self::calculateTotals($set, $get);
                                            }
                                        }
                                    }),
                            ]),

                        Grid::make()
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label(__('resources.start_date'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calculateTotals($set, $get);
                                    }),

                                DatePicker::make('end_date')
                                    ->label(__('resources.end_date'))
                                    ->required()
                                    ->native(false)
                                    ->after('start_date')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calculateTotals($set, $get);
                                    }),
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

                Section::make(__('resources.vehicle_status_location'))
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('status')
                                    ->label(__('resources.booking_status'))
                                    ->options(
                                        collect(bookingstatus::cases())
                                            ->mapWithKeys(fn (bookingstatus $status) => [
                                                $status->value => $status->label(),
                                            ])
                                    )
                                    ->required(),

                                Select::make(__('resources.payment_status'))
                                    ->label(__('resources.payment_status'))
                                    ->options(
                                        collect(PaymentStatus::cases())
                                            ->mapWithKeys(fn (PaymentStatus $status) => [
                                                $status->value => $status->label(),
                                            ])
                                    )
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

    private static function calculateTotals(Set $set, Get $get): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');
        $dailyRate = $get('daily_rate');

        if (!$startDate || !$endDate || !$dailyRate) {
            return;
        }

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $days = $end->diffInDays($start);

            if ($days <= 0) {
                return;
            }

            $subtotal = $days * $dailyRate;
            $insuranceFee = $subtotal * 0.10; // 10% insurance fee
            $taxAmount = $subtotal * 0.15; // 15% tax
            $totalAmount = $subtotal + $insuranceFee + $taxAmount;

            $set('days', $days);
            $set('subtotal', number_format($subtotal, 2, '.', ''));
            $set('insurance_fee', number_format($insuranceFee, 2, '.', ''));
            $set('tax_amount', number_format($taxAmount, 2, '.', ''));
            $set('total_amount', number_format($totalAmount, 2, '.', ''));
        } catch (\Exception $e) {
            // Handle date parsing errors silently
        }
    }
}
