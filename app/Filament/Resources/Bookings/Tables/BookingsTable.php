<?php

namespace App\Filament\Resources\Bookings\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
use App\Services\FilamentQueryOptimizationService;
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
        $optimizationService = app(FilamentQueryOptimizationService::class);

        return $table
            ->modifyQueryUsing(function ($query) use ($optimizationService) {
                // Apply query optimizations
                $query = $optimizationService->optimizePagination($query, 25);
                return $optimizationService->monitorQueryPerformance($query, 'BookingsTable');
            })
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
                    ->formatStateUsing(fn ($record): string => $record->vehicle ? ($record->vehicle->make.' '.$record->vehicle->model) : 'N/A')
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
                    ->formatStateUsing(fn ($state): string => $state instanceof BookingStatus ? $state->label() : (string) $state)
                    ->getStateUsing(fn ($record): string => $record->status instanceof BookingStatus ? $record->status->label() : (string) $record->status)
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
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentStatus ? $state->label() : (string) $state)
                    ->getStateUsing(fn ($record): string => $record->payment_status instanceof PaymentStatus ? $record->payment_status->label() : (string) $record->payment_status)
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

                //                Select::make('transmission')
                //                    ->label(__('resources.transmission'))
                //                    ->options([
                //                        'automatic' => __('enums.transmission.automatic'),
                //                        'manual' => __('enums.transmission.manual'),
                //                        'cvt' => __('enums.transmission.cvt'),
                //                    ])

                SelectFilter::make('status')
                    ->label(__('resources.booking_status'))
                    ->options([
                        'pending' => __('enums.booking_status.pending'),
                        'confirmed' => __('enums.booking_status.confirmed'),
                        'ongoing' => __('enums.booking_status.ongoing'),
                        'completed' => __('enums.booking_status.completed'),
                        'cancelled' => __('enums.booking_status.cancelled'),
                    ])
                    ->multiple(),

                SelectFilter::make('payment_status')
                    ->label(__('resources.payment_status'))
                    ->options([
                        'unpaid' => __('enums.payment_status.unpaid'),
                        'pending' => __('enums.payment_status.pending'),
                        'confirmed' => __('enums.payment_status.confirmed'),
                        'failed' => __('enums.payment_status.failed'),
                        'refunded' => __('enums.payment_status.refunded'),
                        'cancelled' => __('enums.payment_status.cancelled'),
                        'processing' => __('enums.payment_status.processing'),
                    ])
                    ->multiple(),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label(__('widgets.export'))
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (): bool => auth()->user() && in_array(auth()->user()->role, ['admin', 'owner', 'renter']))
                    ->modalHeading(fn ($record): string => __('resources.booking').' #'.$record->id)
                    ->infolist(fn (): array => BookingInfolist::configure(new \Filament\Schemas\Schema)->getComponents()),
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
                        auth()->user() && auth()->user()->role == 'admin')
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

                    // Custom bulk status update with optimization
                    Action::make('bulk_status_update')
                        ->label(__('bookings.bulk_status_update'))
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->form([
                            Select::make('status')
                                ->label(__('bookings.status'))
                                ->options([
                                    BookingStatus::CONFIRMED->value => __('enums.booking_status.confirmed'),
                                    BookingStatus::CANCELLED->value => __('enums.booking_status.cancelled'),
                                    BookingStatus::COMPLETED->value => __('enums.booking_status.completed'),
                                ])
                                ->required(),
                            Textarea::make('reason')
                                ->label(__('bookings.reason'))
                                ->placeholder(__('bookings.bulk_update_reason_placeholder'))
                                ->maxLength(500),
                        ])
                        ->action(function (array $data, $records) {
                            $optimizationService = app(FilamentQueryOptimizationService::class);
                            $recordIds = $records->pluck('id')->toArray();

                            // Use optimized bulk operation
                            $optimizationService->getBulkOperationQuery('Booking', $recordIds)
                                ->update([
                                    'status' => $data['status'],
                                    'notes' => $data['reason'] ?? null,
                                    'updated_at' => now(),
                                ]);

                            // Show success message
                            $count = count($recordIds);
                            \Filament\Notifications\Notification::make()
                                ->title(__('bookings.bulk_update_success', ['count' => $count]))
                                ->success()
                                ->send();
                        }),

                    FilamentExportBulkAction::make('bulk_export')
                        ->label(__('widgets.export'))
                        ->icon('heroicon-m-arrow-down-tray'),
                ]),
            ]);
    }
}
