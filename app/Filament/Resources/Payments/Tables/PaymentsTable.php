<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Payments\Schemas\PaymentInfolist;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
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
                    ->money(config('app.currency', 'MYR'))
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentMethod ? $state->label() : (string) $state)
                    ->getStateUsing(fn ($record) => $record->payment_method instanceof PaymentMethod ? $record->payment_method->label() : (string) $record->payment_method)
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label(__('resources.status'))
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentStatus ? $state->label() : (string) $state)
                    ->getStateUsing(fn ($record) => $record->payment_status instanceof PaymentStatus ? $record->payment_status->label() : (string) $record->payment_status)
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
                //                SelectFilter::make('payment_status')
                //                    ->label(__('resources.payment_status'))
                //                    ->options(collect(PaymentStatus::cases())->pluck('label', 'value'))
                //                    ->multiple(),

                //                SelectFilter::make('payment_method')
                //                    ->label(__('resources.payment_method'))
                //                    ->options(collect(PaymentMethod::cases())->pluck('label', 'value'))
                //                    ->multiple(),

                //                SelectFilter::make('booking_id')
                //                    ->label(__('resources.booking_id'))
                //                    ->relationship('booking', 'id')
                //                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (): bool => auth()->user() && in_array(auth()->user()->role, UserRole::values()))
                    ->modalHeading(fn ($record): string => __('resources.payment').' #'.$record->id)
                    ->infolist(fn (): array => PaymentInfolist::configure(new \Filament\Schemas\Schema)->getComponents()),
                EditAction::make()->visible(fn (): bool => auth()->user() && auth()->user()->role === UserRole::ADMIN->value),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('resources.no_payments'))
            ->emptyStateDescription(__('resources.no_payments_description'))
            ->emptyStateIcon('heroicon-o-credit-card')
            ->defaultSort('created_at', 'desc');
    }
}
