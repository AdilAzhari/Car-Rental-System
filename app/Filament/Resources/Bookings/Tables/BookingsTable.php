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
                    ->money(config('app.currency', 'MYR'))
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
                        'warning' => 'unpaid',
                        'success' => 'paid',
                        'secondary' => 'refunded',
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
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
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

                Action::make('confirm_booking')
                    ->label('Confirm Booking')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'pending_payment']) &&
                        auth()->user() && in_array(auth()->user()->role, ['admin', 'owner']))
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Booking')
                    ->modalDescription('Are you sure you want to confirm this booking? This will change the status to confirmed.')
                    ->modalSubmitActionLabel('Confirm Booking')
                    ->form([
                        Textarea::make('notes')
                            ->label('Confirmation Notes')
                            ->placeholder('Add any notes about the booking confirmation...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => 'confirmed',
                        ]);

                        // Add a note to special_requests if provided
                        if (! empty($data['notes'])) {
                            $existingNotes = $record->special_requests;
                            $record->update([
                                'special_requests' => $existingNotes ? $existingNotes."\n\nAdmin Notes: ".$data['notes'] : 'Admin Notes: '.$data['notes'],
                            ]);
                        }
                    }),

                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->color('success')
                    ->visible(fn ($record): bool => $record->payment_status === 'unpaid' &&
                        auth()->user() && in_array(auth()->user()->role, ['admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Mark Payment as Paid')
                    ->modalDescription('Are you sure you want to mark this payment as paid? This action should only be done after receiving payment.')
                    ->modalSubmitActionLabel('Mark as Paid')
                    ->form([
                        Textarea::make('payment_notes')
                            ->label('Payment Confirmation Notes')
                            ->placeholder('Add details about how payment was received...')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'payment_status' => 'paid',
                        ]);

                        // Create a payment record if one doesn't exist
                        if (! $record->payments()->exists()) {
                            $record->payments()->create([
                                'amount' => $record->total_amount,
                                'payment_method' => $record->payment_method ?: 'cash',
                                'payment_gateway' => 'manual',
                                'status' => 'paid',
                                'notes' => $data['payment_notes'],
                                'processed_at' => now(),
                            ]);
                        } else {
                            // Update the existing payment
                            $payment = $record->payments()->latest()->first();
                            $payment->update([
                                'status' => 'paid',
                                'notes' => ($payment->notes ? $payment->notes."\n\n" : '').'Admin confirmed: '.$data['payment_notes'],
                                'processed_at' => now(),
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
