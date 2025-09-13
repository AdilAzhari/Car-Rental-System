<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.booking_id'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('renter.name')
                    ->label(__('resources.renter'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vehicle.model')
                    ->label(__('resources.vehicle'))
                    ->formatStateUsing(fn ($record): string => $record->vehicle->make.' '.$record->vehicle->model)
                    ->searchable(['make', 'model'])
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('resources.start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('resources.end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('days')
                    ->label(__('resources.days'))
                    ->numeric()
                    ->sortable(false),

                TextColumn::make('total_amount')
                    ->label(__('resources.total_amount'))
                    ->money('USD')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label(__('resources.booking_status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'ongoing',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label(__('resources.payment_status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'processing',
                        'danger' => 'failed',
                        'secondary' => 'refunded',
                        'gray' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('resources.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('status')
                    ->label(__('resources.booking_status'))
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

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
                    ->relationship('payment', 'payment_method')
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
            ])
            ->recordActions([
                ViewAction::make()->visible(fn (): bool => auth()->user() && in_array(auth()->user()->role, ['admin', 'owner', 'renter'])),
                EditAction::make()->visible(fn (): bool => auth()->user() && in_array(auth()->user()->role, ['admin', 'owner'])),

                Action::make('confirm_payment')
                    ->label(__('resources.confirm_payment'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn ($record): bool => $record->payment_status === 'pending' &&
                        $record->payments()->where('payment_method', 'cash')->exists())
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Cash Payment')
                    ->modalDescription('Are you sure you want to confirm this cash payment?')
                    ->modalSubmitActionLabel(__('resources.confirm_payment'))
                    ->form([
                        Textarea::make('notes')
                            ->label(__('resources.payment_notes'))
                            ->placeholder('Add any notes about the payment confirmation...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $payment = $record->payments()->where('payment_method', 'cash')->latest()->first();

                        if ($payment) {
                            $payment->update([
                                'status' => 'confirmed',
                                'notes' => $data['notes'] ?? null,
                                'confirmed_at' => now(),
                            ]);

                            $record->update([
                                'status' => 'confirmed',
                                'payment_status' => 'confirmed',
                            ]);
                        }
                    }),

                Action::make('update_status')
                    ->label(__('resources.update_status'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('info')
                    ->form([
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
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),

                Action::make('view_payments')
                    ->label(__('resources.view_payments'))
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->color('gray')
                    ->url(fn ($record): string => route('filament.admin.resources.payments.index', [
                        'tableFilters' => [
                            'booking_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
