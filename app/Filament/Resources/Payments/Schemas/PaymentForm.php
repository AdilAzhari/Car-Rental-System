<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use Exception;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Carbon\Carbon;

class PaymentForm
{
    /**
     * @throws Exception
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('PaymentTabs')
                    ->tabs([
                        Tab::make('Payment Details')
                            ->icon('heroicon-m-credit-card')
                            ->schema([
                                Section::make('Booking Information')
                                    ->description('Select the booking this payment is for')
                                    ->icon('heroicon-m-calendar-days')
                                    ->schema([
                                        Select::make('booking_id')
                                            ->label('Booking')
                                            ->options(function () {
                                                return Booking::with(['renter', 'vehicle'])
                                                    ->get()
                                                    ->mapWithKeys(function ($booking) {
                                                        return [
                                                            $booking->id => "#{$booking->id} - {$booking->renter->name} ({$booking->vehicle->make} {$booking->vehicle->model}) - \${$booking->total_amount}"
                                                        ];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->placeholder('Select a booking')
                                            ->helperText('Choose the booking this payment is associated with')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $booking = Booking::query()->find($state);
                                                    if ($booking) {
                                                        $set('amount', $booking->total_amount);
                                                        $set('currency', 'MYR');
                                                        $set('description', "Payment for booking #{$booking->id}");
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Payment Amount')
                                    ->description('Set the payment amount and currency')
                                    ->icon('heroicon-m-banknotes')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 3])
                                            ->schema([
                                                TextInput::make('amount')
                                                    ->label('Payment Amount')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->maxValue(999999)
                                                    ->placeholder('0.00')
                                                    ->helperText('Amount to be paid')
                                                    ->live(),

                                                Select::make('currency')
                                                    ->label('Currency')
                                                    ->options([
                                                        'MYR' => '🇲🇾 Malaysian Ringgit (RM)',
                                                        'USD' => '🇺🇸 US Dollar (USD)',
                                                    ])
                                                    ->default('MYR')
                                                    ->required()
                                                    ->native(false)
                                                    ->helperText('Payment currency'),

                                                Select::make('type')
                                                    ->label('Payment Type')
                                                    ->options([
                                                        'rental_payment' => '🚗 Rental Payment',
                                                        'deposit' => '🛡️ Security Deposit',
                                                        'additional_charge' => '📋 Additional Charge',
                                                        'refund' => '💰 Refund',
                                                        'partial_payment' => '📊 Partial Payment',
                                                    ])
                                                    ->default('rental_payment')
                                                    ->required()
                                                    ->native(false)
                                                    ->helperText('Type of payment'),
                                            ]),
                                    ]),

                                Section::make('Payment Method & Status')
                                    ->description('Select payment method and current status')
                                    ->icon('heroicon-m-credit-card')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                Select::make('method')
                                                    ->label('Payment Method')
                                                    ->options([
                                                        'stripe' => '🔷 Stripe (Credit/Debit Card)',
                                                        'tng' => '📱 Touch \'n Go eWallet',
                                                        'cash' => '💵 Cash (In-Store)',
                                                    ])
                                                    ->required()
                                                    ->native(false)
                                                    ->placeholder('Select payment method')
                                                    ->helperText('Choose payment method: Online (Stripe/TNG) or In-Store (Cash)')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        // Set default transaction references based on method
                                                        match ($state) {
                                                            'stripe' => $set('gateway_reference', 'Stripe Payment Intent: '),
                                                            'tng' => $set('gateway_reference', 'TNG Transaction: '),
                                                            'cash' => $set('gateway_reference', 'Cash Receipt: '),
                                                            default => null,
                                                        };
                                                    }),

                                                Select::make('status')
                                                    ->label('Payment Status')
                                                    ->options(PaymentStatus::class)
                                                    ->default(PaymentStatus::PENDING->value)
                                                    ->required()
                                                    ->native(false)
                                                    ->helperText('Current status of the payment')
                                                    ->live(),
                                            ]),

                                        Group::make([
                                            Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('transaction_reference')
                                                    ->label(function (callable $get) {
                                                        return match ($get('method')) {
                                                            'stripe' => 'Stripe Payment Intent ID',
                                                            'tng' => 'TNG Transaction ID',
                                                            'cash' => 'Cash Receipt Number',
                                                            default => 'Transaction Reference',
                                                        };
                                                    })
                                                    ->maxLength(255)
                                                    ->placeholder(function (callable $get) {
                                                        return match ($get('method')) {
                                                            'stripe' => 'pi_1234567890abcdef',
                                                            'tng' => 'TNG202501081234567',
                                                            'cash' => 'CASH-001-20250108',
                                                            default => 'Enter transaction reference',
                                                        };
                                                    })
                                                    ->helperText(function (callable $get) {
                                                        return match ($get('method')) {
                                                            'stripe' => 'Stripe Payment Intent ID from successful charge',
                                                            'tng' => 'Touch \'n Go transaction reference number',
                                                            'cash' => 'Physical receipt or transaction number',
                                                            default => 'Unique identifier for this payment',
                                                        };
                                                    })
                                                    ->required(fn (callable $get) => in_array($get('method'), ['stripe', 'tng']))
                                                    ->live(),

                                                TextInput::make('external_id')
                                                    ->label('External Reference')
                                                    ->maxLength(255)
                                                    ->placeholder(function (callable $get) {
                                                        return match ($get('method')) {
                                                            'stripe' => 'ch_1234567890abcdef',
                                                            'tng' => 'TNG-MERCHANT-REF-123',
                                                            'cash' => 'Store location/staff ID',
                                                            default => 'External reference',
                                                        };
                                                    })
                                                    ->helperText(function (callable $get) {
                                                        return match ($get('method')) {
                                                            'stripe' => 'Stripe Charge ID or Customer ID',
                                                            'tng' => 'Your merchant reference for TNG',
                                                            'cash' => 'Store ID, staff member, or location',
                                                            default => 'Secondary reference if needed',
                                                        };
                                                    }),
                                            ]),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Transaction Info')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Section::make('Transaction Details')
                                    ->description('Additional transaction information')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                DateTimePicker::make('processed_at')
                                                    ->label('Processed Date & Time')
                                                    ->placeholder('When was payment processed?')
                                                    ->seconds(false)
                                                    ->timezone(config('app.timezone'))
                                                    ->displayFormat('M j, Y H:i')
                                                    ->helperText('When the payment was actually processed')
                                                    ->visible(fn (callable $get) => in_array($get('status'), [
                                                        PaymentStatus::CONFIRMED->value,
                                                        PaymentStatus::PROCESSING->value,
                                                        PaymentStatus::FAILED->value,
                                                    ])),

                                                TextInput::make('gateway_fee')
                                                    ->label('Gateway Fee')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->placeholder('0.00')
                                                    ->helperText('Payment processing fee'),
                                            ]),

                                        TextInput::make('gateway_reference')
                                            ->label(function (callable $get) {
                                                return match ($get('method')) {
                                                    'stripe' => 'Stripe Response Data',
                                                    'tng' => 'TNG Gateway Response',
                                                    'cash' => 'Cash Verification Details',
                                                    default => 'Gateway Reference',
                                                };
                                            })
                                            ->maxLength(500)
                                            ->placeholder(function (callable $get) {
                                                return match ($get('method')) {
                                                    'stripe' => 'JSON response or webhook data from Stripe',
                                                    'tng' => 'TNG API response or callback data',
                                                    'cash' => 'Staff verification or store details',
                                                    default => 'Gateway response data',
                                                };
                                            })
                                            ->helperText(function (callable $get) {
                                                return match ($get('method')) {
                                                    'stripe' => 'Store Stripe webhook data or API response',
                                                    'tng' => 'Store TNG payment callback or response data',
                                                    'cash' => 'Store cash handling verification details',
                                                    default => 'Technical data from payment processor',
                                                };
                                            }),

                                        TextInput::make('payer_name')
                                            ->label('Payer Name')
                                            ->maxLength(255)
                                            ->placeholder('Name of person who made payment')
                                            ->helperText('Full name of the payer'),

                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('payer_email')
                                                    ->label('Payer Email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('payer@example.com')
                                                    ->helperText('Email address of payer'),

                                                TextInput::make('payer_phone')
                                                    ->label('Payer Phone')
                                                    ->tel()
                                                    ->maxLength(20)
                                                    ->placeholder('+1 (555) 123-4567')
                                                    ->helperText('Phone number of payer'),
                                            ]),

                                        Textarea::make('description')
                                            ->label('Payment Description')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Additional details about this payment...')
                                            ->helperText('Optional description or notes')
                                            ->columnSpanFull(),
                                    ]),

                                // Stripe-specific section
                                Section::make('Stripe Payment Details')
                                    ->description('Stripe-specific payment information and metadata')
                                    ->icon('heroicon-m-credit-card')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('stripe_customer_id')
                                                    ->label('Stripe Customer ID')
                                                    ->maxLength(255)
                                                    ->placeholder('cus_1234567890abcdef')
                                                    ->helperText('Stripe Customer ID for recurring payments'),

                                                TextInput::make('stripe_payment_method_id')
                                                    ->label('Payment Method ID')
                                                    ->maxLength(255)
                                                    ->placeholder('pm_1234567890abcdef')
                                                    ->helperText('Stripe Payment Method used'),
                                            ]),

                                        Grid::make(['default' => 1, 'md' => 3])
                                            ->schema([
                                                TextInput::make('card_brand')
                                                    ->label('Card Brand')
                                                    ->maxLength(50)
                                                    ->placeholder('visa, mastercard, amex')
                                                    ->helperText('Credit/debit card brand'),

                                                TextInput::make('card_last_four')
                                                    ->label('Card Last 4 Digits')
                                                    ->maxLength(4)
                                                    ->placeholder('1234')
                                                    ->helperText('Last 4 digits of card'),

                                                TextInput::make('card_country')
                                                    ->label('Card Country')
                                                    ->maxLength(2)
                                                    ->placeholder('MY, US, SG')
                                                    ->helperText('Card issuing country'),
                                            ]),
                                    ])
                                    ->visible(fn (callable $get) => $get('method') === 'stripe')
                                    ->collapsible(),

                                // TNG-specific section
                                Section::make('Touch \'n Go Payment Details')
                                    ->description('TNG eWallet payment information and verification')
                                    ->icon('heroicon-m-device-phone-mobile')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('tng_phone_number')
                                                    ->label('TNG Registered Phone')
                                                    ->tel()
                                                    ->maxLength(20)
                                                    ->placeholder('+60123456789')
                                                    ->helperText('Phone number linked to TNG wallet'),

                                                TextInput::make('tng_reference_id')
                                                    ->label('TNG Reference ID')
                                                    ->maxLength(255)
                                                    ->placeholder('TNG-REF-1234567890')
                                                    ->helperText('TNG internal reference number'),
                                            ]),

                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                Select::make('tng_verification_status')
                                                    ->label('TNG Verification Status')
                                                    ->options([
                                                        'pending' => '⏳ Pending Verification',
                                                        'verified' => '✅ Verified',
                                                        'failed' => '❌ Verification Failed',
                                                        'expired' => '⏰ Verification Expired',
                                                    ])
                                                    ->native(false)
                                                    ->helperText('TNG payment verification status'),

                                                DateTimePicker::make('tng_verified_at')
                                                    ->label('Verified Date & Time')
                                                    ->seconds(false)
                                                    ->helperText('When TNG payment was verified'),
                                            ]),
                                    ])
                                    ->visible(fn (callable $get) => $get('method') === 'tng')
                                    ->collapsible(),

                                // Cash-specific section
                                Section::make('Cash Payment Details')
                                    ->description('In-store cash payment verification and handling')
                                    ->icon('heroicon-m-banknotes')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('cash_received_by')
                                                    ->label('Received By (Staff)')
                                                    ->maxLength(255)
                                                    ->placeholder('Staff name or ID')
                                                    ->helperText('Staff member who received cash payment')
                                                    ->required(fn (callable $get) => $get('method') === 'cash'),

                                                TextInput::make('store_location')
                                                    ->label('Store/Branch Location')
                                                    ->maxLength(255)
                                                    ->placeholder('Main Branch, Kuala Lumpur')
                                                    ->helperText('Physical location where payment was made'),
                                            ]),

                                        Grid::make(['default' => 1, 'md' => 3])
                                            ->schema([
                                                TextInput::make('cash_amount_received')
                                                    ->label('Cash Amount Received')
                                                    ->numeric()
                                                    ->prefix('RM')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->helperText('Actual cash amount received'),

                                                TextInput::make('change_given')
                                                    ->label('Change Given')
                                                    ->numeric()
                                                    ->prefix('RM')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->helperText('Change returned to customer'),

                                                Select::make('cash_verification')
                                                    ->label('Cash Verification')
                                                    ->options([
                                                        'counted' => '✅ Amount Counted & Verified',
                                                        'deposited' => '🏦 Deposited to Till/Safe',
                                                        'pending' => '⏳ Pending Verification',
                                                    ])
                                                    ->native(false)
                                                    ->helperText('Cash handling verification status'),
                                            ]),

                                        Textarea::make('cash_notes')
                                            ->label('Cash Payment Notes')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Any additional notes about the cash transaction...')
                                            ->helperText('Special notes, issues, or observations'),
                                    ])
                                    ->visible(fn (callable $get) => $get('method') === 'cash')
                                    ->collapsible(),

                                Section::make('Failure Information')
                                    ->description('Details about payment failure (if applicable)')
                                    ->icon('heroicon-m-exclamation-triangle')
                                    ->schema([
                                        TextInput::make('failure_reason')
                                            ->label('Failure Reason')
                                            ->maxLength(500)
                                            ->placeholder('Insufficient funds, card declined, etc.')
                                            ->helperText('Why the payment failed'),

                                        Textarea::make('failure_details')
                                            ->label('Failure Details')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Detailed error message or additional failure information...')
                                            ->helperText('Technical details about the failure'),
                                    ])
                                    ->visible(fn (callable $get) => $get('status') === PaymentStatus::FAILED->value)
                                    ->collapsible(),

                                Section::make('Refund Information')
                                    ->description('Details about refund (if applicable)')
                                    ->icon('heroicon-m-arrow-uturn-left')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2])
                                            ->schema([
                                                TextInput::make('refund_amount')
                                                    ->label('Refund Amount')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->placeholder('0.00')
                                                    ->helperText('Amount refunded'),

                                                DateTimePicker::make('refunded_at')
                                                    ->label('Refunded Date & Time')
                                                    ->seconds(false)
                                                    ->helperText('When refund was processed'),
                                            ]),

                                        TextInput::make('refund_reference')
                                            ->label('Refund Reference')
                                            ->maxLength(255)
                                            ->placeholder('Refund transaction ID')
                                            ->helperText('Reference for refund transaction'),

                                        Textarea::make('refund_reason')
                                            ->label('Refund Reason')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Reason for refund...')
                                            ->helperText('Why the refund was issued'),
                                    ])
                                    ->visible(fn (callable $get) => $get('status') === PaymentStatus::REFUNDED->value)
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }
}
