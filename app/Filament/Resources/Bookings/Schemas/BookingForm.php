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
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Utilities\Set;
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
                Wizard::make([
                    Step::make('customer-vehicle')
                        ->label(__('resources.customer_vehicle'))
                        ->description(__('resources.select_customer_and_vehicle'))
                        ->icon('heroicon-o-users')
                        ->schema([
                            // Enhanced Customer & Vehicle Selection
                            Section::make(__('resources.customer_selection'))
                                ->description(__('resources.choose_customer_description'))
                                ->icon('heroicon-m-user-circle')
                                ->aside()
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            Select::make('renter_id')
                                                ->label(__('resources.customer'))
                                                ->relationship(
                                                    name: 'renter',
                                                    titleAttribute: 'name',
                                                    modifyQueryUsing: fn ($query) => $query->where('role', 'renter')
                                                )
                                                ->getOptionLabelFromRecordUsing(function (User $record) {
                                                    return "$record->name - $record->email";
                                                })
                                                ->searchable(['name', 'email', 'phone'])
                                                ->preload()
                                                ->required()
                                                ->createOptionForm([
                                                    Grid::make()
                                                        ->schema([
                                                            TextInput::make('name')
                                                                ->label(__('resources.full_name'))
                                                                ->required()
                                                                ->maxLength(255)
                                                                ->placeholder(__('resources.enter_customer_name')),

                                                            TextInput::make('email')
                                                                ->label(__('resources.email'))
                                                                ->email()
                                                                ->required()
                                                                ->maxLength(255)
                                                                ->placeholder(__('resources.customer_email_placeholder')),
                                                        ]),

                                                    TextInput::make('phone')
                                                        ->label(__('resources.phone_number'))
                                                        ->tel()
                                                        ->maxLength(20)
                                                        ->placeholder(__('resources.phone_placeholder')),
                                                ])
                                                ->live()
                                                ->suffixIcon('heroicon-m-user')
                                                ->helperText(__('resources.customer_selection_helper')),

                                            // Customer Info Display
                                            View::make('filament.components.customer-info-card')
                                                ->viewData(function (Get $get) {
                                                    $renterId = $get('renter_id');
                                                    if ($renterId) {
                                                        $customer = User::query()->find($renterId);
                                                        return ['customer' => $customer];
                                                    }
                                                    return ['customer' => null];
                                                })
                                                ->visible(fn (Get $get) => filled($get('renter_id'))),
                                        ]),
                                ]),

                            Section::make(__('resources.vehicle_selection'))
                                ->description(__('resources.choose_vehicle_description'))
                                ->icon('heroicon-m-truck')
                                ->aside()
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            Select::make('vehicle_id')
                                                ->label(__('resources.vehicle'))
                                                ->relationship(
                                                    name: 'vehicle',
                                                    titleAttribute: 'plate_number',
                                                    modifyQueryUsing: fn ($query) => $query->where('status', 'published')->where('is_available', true)
                                                )
                                                ->getOptionLabelFromRecordUsing(function (Vehicle $record) {
                                                    return "$record->make $record->model ($record->year) - $record->plate_number";
                                                })
                                                ->searchable(['plate_number', 'make', 'model', 'year'])
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->suffixIcon('heroicon-m-truck')
                                                ->helperText(__('resources.vehicle_selection_helper'))
                                                ->afterStateUpdated(function (Set $set, $state) {
                                                    if ($state) {
                                                        $vehicle = Vehicle::query()->find($state);
                                                        if ($vehicle) {
                                                            $set('daily_rate', $vehicle->daily_rate);
                                                            $set('pickup_location', $vehicle->location ?? '');
                                                        }
                                                    }
                                                }),

                                            // Vehicle Info Display
                                            View::make('filament.components.vehicle-info-card')
                                                ->viewData(function (Get $get) {
                                                    $vehicleId = $get('vehicle_id');
                                                    if ($vehicleId) {
                                                        $vehicle = Vehicle::query()->find($vehicleId);
                                                        return ['vehicle' => $vehicle];
                                                    }
                                                    return ['vehicle' => null];
                                                })
                                                ->visible(fn (Get $get) => filled($get('vehicle_id'))),
                                        ]),
                                ]),
                        ]),

                    Wizard\Step::make('dates-location')
                        ->label(__('resources.dates_location'))
                        ->description(__('resources.set_rental_period_locations'))
                        ->icon('heroicon-o-calendar-days')
                        ->schema([
                            // Enhanced Date Selection with Visual Calendar
                            Section::make(__('resources.rental_period'))
                                ->description(__('resources.rental_period_description'))
                                ->icon('heroicon-m-calendar-days')
                                ->aside()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            DatePicker::make('start_date')
                                                ->label(__('resources.start_date'))
                                                ->required()
                                                ->native(false)
                                                ->displayFormat('d M Y')
                                                ->format('Y-m-d')
                                                ->minDate(now())
                                                ->maxDate(now()->addMonths(6))
                                                ->suffixIcon('heroicon-m-calendar')
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::calculateTotalAmount($set, $get);
                                                    // Auto-set minimum end date
                                                    if ($startDate = $get('start_date')) {
                                                        if (!$get('end_date') || $get('end_date') < $startDate) {
                                                            $set('end_date', $startDate);
                                                        }
                                                    }
                                                }),

                                            DatePicker::make('end_date')
                                                ->label(__('resources.end_date'))
                                                ->required()
                                                ->native(false)
                                                ->displayFormat('d M Y')
                                                ->format('Y-m-d')
                                                ->after('start_date')
                                                ->minDate(fn (Get $get) => $get('start_date') ?: now())
                                                ->maxDate(now()->addMonths(6))
                                                ->suffixIcon('heroicon-m-calendar')
                                                ->live()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::calculateTotalAmount($set, $get);
                                                }),

                                            // Rental Summary Card
                                            View::make('filament.components.rental-summary-card')
                                                ->viewData(function (Get $get) {
                                                    return [
                                                        'start_date' => $get('start_date'),
                                                        'end_date' => $get('end_date'),
                                                        'vehicle_id' => $get('vehicle_id'),
                                                        'total_amount' => $get('total_amount'),
                                                    ];
                                                })
                                                ->visible(fn (Get $get) => filled($get('start_date')) && filled($get('end_date'))),
                                        ]),
                                ]),

                            // Enhanced Location Selection
                            Section::make(__('resources.pickup_dropoff_locations'))
                                ->description(__('resources.location_details_description'))
                                ->icon('heroicon-m-map-pin')
                                ->aside()
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('pickup_location')
                                                ->label(__('resources.pickup_location'))
                                                ->required()
                                                ->placeholder(__('resources.pickup_location_placeholder'))
                                                ->suffixIcon('heroicon-m-map-pin')
                                                ->maxLength(255)
                                                ->helperText(__('resources.pickup_location_helper')),

                                            TextInput::make('dropoff_location')
                                                ->label(__('resources.dropoff_location'))
                                                ->required()
                                                ->placeholder(__('resources.dropoff_location_placeholder'))
                                                ->suffixIcon('heroicon-m-flag')
                                                ->maxLength(255)
                                                ->helperText(__('resources.dropoff_location_helper')),
                                        ]),
                                ]),
                        ]),

                    Wizard\Step::make('pricing-payment')
                        ->label(__('resources.pricing_payment'))
                        ->description(__('resources.configure_pricing_payment'))
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([

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

                            // Enhanced Pricing Section with Visual Breakdown
                            Section::make(__('resources.pricing_details'))
                                ->description(__('resources.pricing_breakdown_description'))
                                ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Section::make('Pickup & Dropoff')
                                    ->description('Specify pickup and drop off locations')
                                    ->icon(Heroicon::OutlinedMapPin)
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)
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
                                                    ->prefix('MYR')
                                                    ->step(0.01)
                                                    ->live()
                                                    ->dehydrated(),

                                                TextInput::make('deposit_amount')
                                                    ->label('Security Deposit')
                                                    ->numeric()
                                                    ->prefix('MYR')
                                                    ->step(0.01)
                                                    ->default(0.0)
                                                    ->helperText('Refundable security deposit'),

                                                TextInput::make('commission_amount')
                                                    ->label('Commission')
                                                    ->numeric()
                                                    ->prefix('MYR')
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

                                                        return 'Total: MYR '.number_format($total, 2).' | '.
                                                               'Deposit: MYR '.number_format($deposit, 2).' | '.
                                                               'Commission: MYR '.number_format($commission, 2).' | '.
                                                               'Net: MYR '.number_format($net, 2);
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
                                        Grid::make(2)
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
