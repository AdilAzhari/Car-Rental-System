<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Models\User;
use App\Models\Vehicle;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Hamcrest\Core\Set;
use Illuminate\Support\Carbon;

class BookingForm
{
    /**
     * @throws Exception
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('Booking Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Rental Details')
                                    ->description('Select the customer and vehicle for this booking')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                Select::make('renter_id')
                                                    ->label('Customer')
                                                    ->relationship(
                                                        name: 'renter',
                                                        titleAttribute: 'name',
                                                        modifyQueryUsing: fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'renter'))
                                                    )
                                                    ->searchable(['name', 'email'])
                                                    ->preload()
                                                    ->required()
                                                    ->createOptionForm([
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        TextInput::make('email')
                                                            ->email()
                                                            ->required()
                                                            ->maxLength(255),
                                                    ])
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        if ($state) {
                                                            $customer = User::query()->find($state);
                                                            if ($customer) {
                                                                $set('customer_info', $customer->email);
                                                            }
                                                        }
                                                    }),

                                                Placeholder::make('customer_info')
                                                    ->label('Customer Email')
                                                    ->content(function (Get $get): string {
                                                        if ($renterId = $get('renter_id')) {
                                                            $customer = User::query()->find($renterId);

                                                            return $customer?->email ?? 'No email found';
                                                        }

                                                        return 'Select a customer first';
                                                    })
                                                    ->visible(function (Get $get): bool {
                                                        return filled($get('renter_id'));
                                                    }),
                                            ]),

                                        Grid::make()
                                            ->schema([
                                                Select::make('vehicle_id')
                                                    ->label('Vehicle')
                                                    ->relationship(
                                                        name: 'vehicle',
                                                        titleAttribute: 'plate_number',
                                                        modifyQueryUsing: fn ($query) => $query->where('status', 'available')
                                                    )
                                                    ->getOptionLabelFromRecordUsing(function (Vehicle $record) {
                                                        return "$record->plate_number - $record->make $record->model ($record->year)";
                                                    })
                                                    ->searchable(['plate_number', 'make', 'model'])
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, $state) {
                                                        if ($state) {
                                                            $vehicle = Vehicle::query()->find($state);
                                                            if ($vehicle) {
                                                                $set('daily_rate', $vehicle->daily_rate);
                                                            }
                                                        }
                                                    }),

                                                Placeholder::make('vehicle_info')
                                                    ->label('Daily Rate')
                                                    ->content(function (Get $get): string {
                                                        if ($vehicleId = $get('vehicle_id')) {
                                                            $vehicle = Vehicle::query()->find($vehicleId);

                                                            return $vehicle ? '$'.number_format($vehicle->daily_rate, 2).'/day' : 'Rate not found';
                                                        }

                                                        return 'Select a vehicle first';
                                                    })
                                                    ->visible(function (Get $get): bool {
                                                        return filled($get('vehicle_id'));
                                                    }),
                                            ]),
                                    ]),

                                Section::make('Rental Period')
                                    ->description('Set the rental start and end dates')
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                DatePicker::make('start_date')
                                                    ->label('Start Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('M d, Y')
                                                    ->minDate(now())
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                                        self::calculateTotalAmount($set, $get);
                                                    }),

                                                DatePicker::make('end_date')
                                                    ->label('End Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('M d, Y')
                                                    ->after('start_date')
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                                        self::calculateTotalAmount($set, $get);
                                                    }),

                                                Placeholder::make('rental_days')
                                                    ->label('Rental Days')
                                                    ->content(function (Get $get): string {
                                                        $start = $get('start_date');
                                                        $end = $get('end_date');
                                                        if ($start && $end) {
                                                            $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));

                                                            return ($days + 1).' days';
                                                        }

                                                        return 'Select dates';
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Location & Pricing')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Section::make('Pickup & Dropoff')
                                    ->description('Specify pickup and drop off locations')
                                    ->icon(Heroicon::OutlinedMapPin)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextInput::make('pickup_location')
                                                    ->label('Pickup Location')
                                                    ->required()
                                                    ->placeholder('Enter pickup address')
                                                    ->suffixIcon(Heroicon::OutlinedMapPin)
                                                    ->maxLength(255),

                                                TextInput::make('dropoff_location')
                                                    ->label('Drop off Location')
                                                    ->required()
                                                    ->placeholder('Enter drop off address')
                                                    ->suffixIcon(Heroicon::OutlinedMapPin)
                                                    ->maxLength(255),
                                            ]),
                                    ]),

                                Section::make('Pricing Details')
                                    ->description('Manage pricing and costs')
                                    ->icon(Heroicon::OutlinedCurrencyDollar)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('total_amount')
                                                    ->label('Total Amount')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->live()
                                                    ->dehydrated(),

                                                TextInput::make('deposit_amount')
                                                    ->label('Security Deposit')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->default(0.0)
                                                    ->helperText('Refundable security deposit'),

                                                TextInput::make('commission_amount')
                                                    ->label('Commission')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->default(0.0)
                                                    ->helperText('Platform commission'),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Placeholder::make('pricing_summary')
                                                    ->label('Pricing Summary')
                                                    ->content(function (Get $get): string {
                                                        $total = (float) $get('total_amount') ?: 0;
                                                        $deposit = (float) $get('deposit_amount') ?: 0;
                                                        $commission = (float) $get('commission_amount') ?: 0;
                                                        $net = $total - $commission;

                                                        return 'Total: $'.number_format($total, 2).' | '.
                                                               'Deposit: $'.number_format($deposit, 2).' | '.
                                                               'Commission: $'.number_format($commission, 2).' | '.
                                                               'Net: $'.number_format($net, 2);
                                                    })
                                                    ->visible(function (Get $get): bool {
                                                        return filled($get('total_amount'));
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Status & Payment')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->schema([
                                Section::make('Booking Status')
                                    ->description('Current status of the booking')
                                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                Select::make('status')
                                                    ->label('Booking Status')
                                                    ->options(collect(BookingStatus::cases())->mapWithKeys(function ($case) {
                                                        return [$case->value => $case->label()];
                                                    }))
                                                    ->default(BookingStatus::PENDING->value)
                                                    ->required()
                                                    ->native(false)
                                                    ->suffixIcon(Heroicon::OutlinedClipboardDocumentCheck),
                                            ]),
                                    ]),

                                Section::make('Payment Information')
                                    ->description('Payment status and method details')
                                    ->icon(Heroicon::OutlinedCreditCard)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                Select::make('payment_status')
                                                    ->label('Payment Status')
                                                    ->options([
                                                        'unpaid' => 'Unpaid',
                                                        'paid' => 'Paid',
                                                        'refunded' => 'Refunded',
                                                    ])
                                                    ->default('unpaid')
                                                    ->required()
                                                    ->native(false)
                                                    ->suffixIcon(Heroicon::OutlinedBanknotes),

                                                Select::make('payment_method')
                                                    ->label('Payment Method')
                                                    ->options([
                                                        'visa' => 'Visa',
                                                        'credit_card' => 'Credit Card',
                                                        'cash' => 'Cash',
                                                    ])
                                                    ->required()
                                                    ->native(false)
                                                    ->suffixIcon(Heroicon::OutlinedCreditCard),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Additional Notes')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Special Requests')
                                    ->description('Any special requirements or notes for this booking')
                                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                                    ->collapsible()
                                    ->schema([
                                        Textarea::make('special_requests')
                                            ->label('Special Requests & Notes')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->placeholder('Enter any special requests, instructions, or notes for this booking...')
                                            ->helperText('Maximum 1000 characters'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function calculateTotalAmount(Set $set, Get $get): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');
        $vehicleId = $get('vehicle_id');

        if ($startDate && $endDate && $vehicleId) {
            $vehicle = Vehicle::query()->find($vehicleId);
            if ($vehicle) {
                $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
                $total = $days * $vehicle->daily_rate;
                $set('total_amount', number_format($total, 2, '.', ''));
            }
        }
    }
}
