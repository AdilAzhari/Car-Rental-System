<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Information')
                    ->schema([
                        Select::make('booking_id')
                            ->label('Booking')
                            ->relationship('booking', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "Booking #{$record->id} - {$record->renter->name}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('RM')
                            ->step('0.01')
                            ->required(),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'stripe' => 'Stripe',
                                'visa' => 'Visa',
                                'credit' => 'Credit Card',
                                'tng' => 'Touch n Go',
                                'touch_n_go' => 'Touch n Go',
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                            ])
                            ->required(),

                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                                'cancelled' => 'Cancelled',
                                'unpaid' => 'Unpaid',
                            ])
                            ->default('pending')
                            ->required(),

                        TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->unique(ignoreRecord: true),

                        Textarea::make('gateway_response')
                            ->label('Gateway Response')
                            ->columnSpanFull()
                            ->rows(3),

                        DateTimePicker::make('processed_at')
                            ->label('Processed At'),

                        TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('RM')
                            ->step('0.01'),

                        DateTimePicker::make('refunded_at')
                            ->label('Refunded At'),
                    ])
                    ->columns(2),
            ]);
    }
}
