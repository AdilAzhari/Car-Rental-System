<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('booking.id')
                            ->label('Booking ID')
                            ->prefix('#'),

                        TextEntry::make('booking.renter.name')
                            ->label('Renter'),

                        TextEntry::make('amount')
                            ->label('Amount')
                            ->money('MYR'),

                        TextEntry::make('method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn ($state) => PaymentMethod::tryFrom($state)?->label() ?? $state)
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('status')
                            ->label('Payment Status')
                            ->formatStateUsing(fn ($state) => PaymentStatus::tryFrom($state)?->label() ?? $state)
                            ->badge()
                            ->color(fn ($state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                default => 'secondary',
                            }),

                        TextEntry::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->placeholder('N/A'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
