<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.payment_id'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('booking.id')
                    ->label(__('resources.booking_id'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('booking.renter.name')
                    ->label(__('resources.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('resources.amount'))
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->badge()
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label(__('resources.status'))
                    ->colors([
                        'info' => 'processing',
                        'success' => 'confirmed',
                        'danger' => 'failed',
                        'secondary' => 'refunded',
                        'gray' => 'cancelled',
                        'warning' => 'unpaid',
                    ])
                    ->sortable(),

                TextColumn::make('transaction_id')
                    ->label(__('resources.transaction_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('processed_at')
                    ->label(__('resources.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('resources.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')
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
                    ->multiple(),

                SelectFilter::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->options([
                        'stripe' => 'Stripe',
                        'visa' => 'Visa',
                        'credit' => 'Credit Card',
                        'tng' => 'Touch n Go',
                        'touch_n_go' => 'Touch n Go',
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                    ])
                    ->multiple(),

                SelectFilter::make('booking_id')
                    ->label(__('resources.booking_id'))
                    ->relationship('booking', 'id')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn (): bool => auth()->user() && in_array(auth()->user()->role, ['admin', 'owner', 'renter'])),
                EditAction::make()->visible(fn (): bool => auth()->user() && auth()->user()->role === 'admin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
