<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class BookingsTable
{
    /**
     * @throws Exception
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn () => \App\Models\Booking::query()->with(['renter', 'vehicle', 'payment']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->prefix('#')
                    ->sortable()
                    ->size('sm')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->copyMessage('Booking ID copied!')
                    ->tooltip('Click to copy'),

                TextColumn::make('renter.name')
                    ->label('Customer')
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->renter?->email)
                    ->icon('heroicon-m-user')
                    ->wrap(),

                TextColumn::make('vehicle.make')
                    ->label('Vehicle')
                    ->formatStateUsing(fn ($record) => $record->vehicle
                        ? "{$record->vehicle->make} {$record->vehicle->model} ({$record->vehicle->year})"
                        : 'N/A')
                    ->searchable(['make', 'model', 'plate_number'])
                    ->sortable(['make', 'model'])
                    ->description(fn ($record) => $record->vehicle?->plate_number)
                    ->icon('heroicon-m-truck')
                    ->weight('medium')
                    ->wrap(),

                TextColumn::make('booking_period')
                    ->label('Rental Period')
                    ->formatStateUsing(function ($record) {
                        $start = Carbon::parse($record->start_date);
                        $end = Carbon::parse($record->end_date);
                        $days = $end->diffInDays($start) + 1;

                        return $start->format('M j') . ' - ' . $end->format('M j, Y') . " ({$days}d)";
                    })
                    ->description(fn ($record) => Carbon::parse($record->start_date)->diffForHumans())
                    ->sortable('start_date')
                    ->icon('heroicon-m-calendar-days')
                    ->wrap(),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->description(fn ($record) => $record->deposit_amount
                        ? 'Deposit: RM ' . number_format($record->deposit_amount, 2)
                        : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof BookingStatus
                        ? $state->label()
                        : BookingStatus::from($state)->label())
                    ->color(fn ($state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                        BookingStatus::PENDING => 'warning',
                        BookingStatus::CONFIRMED => 'info',
                        BookingStatus::ONGOING => 'primary',
                        BookingStatus::COMPLETED => 'success',
                        BookingStatus::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                        BookingStatus::PENDING => 'heroicon-m-clock',
                        BookingStatus::CONFIRMED => 'heroicon-m-check-circle',
                        BookingStatus::ONGOING => 'heroicon-m-play',
                        BookingStatus::COMPLETED => 'heroicon-m-check-badge',
                        BookingStatus::CANCELLED => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentStatus
                        ? $state->label()
                        : PaymentStatus::from($state)->label())
                    ->color(fn ($state): string => match ($state instanceof PaymentStatus ? $state : PaymentStatus::from($state)) {
                        PaymentStatus::PENDING => 'warning',
                        PaymentStatus::CONFIRMED => 'success',
                        PaymentStatus::FAILED => 'danger',
                        PaymentStatus::REFUNDED => 'info',
                        PaymentStatus::CANCELLED => 'secondary',
                        PaymentStatus::PROCESSING => 'primary',
                        PaymentStatus::UNPAID => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state): string => match ($state instanceof PaymentStatus ? $state : PaymentStatus::from($state)) {
                        PaymentStatus::PENDING => 'heroicon-m-clock',
                        PaymentStatus::CONFIRMED => 'heroicon-m-check-circle',
                        PaymentStatus::FAILED => 'heroicon-m-x-circle',
                        PaymentStatus::REFUNDED => 'heroicon-m-arrow-uturn-left',
                        PaymentStatus::CANCELLED => 'heroicon-m-minus-circle',
                        PaymentStatus::PROCESSING => 'heroicon-m-arrow-path',
                        PaymentStatus::UNPAID => 'heroicon-m-exclamation-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable()
                    ->description(fn ($record) => $record->payment_method
                        ? ucwords(str_replace('_', ' ', $record->payment_method))
                        : 'No method'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => Carbon::parse($record->created_at)->format('Y-m-d'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Quick Status Filters
                SelectFilter::make('status')
                    ->label('Booking Status')
                    ->options(BookingStatus::class)
                    ->multiple()
                    ->preload(),

                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options(PaymentStatus::class)
                    ->multiple()
                    ->preload(),

                // Advanced Date Filters
                Filter::make('date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date_from')
                                    ->label('Rental Starts From')
                                    ->placeholder('Select start date'),
                                DatePicker::make('start_date_to')
                                    ->label('Rental Starts To')
                                    ->placeholder('Select end date'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['start_date_from'] ?? null) {
                            $indicators[] = Indicator::make('Rentals from: ' . Carbon::parse($data['start_date_from'])->format('M j, Y'))
                                ->removeField('start_date_from');
                        }

                        if ($data['start_date_to'] ?? null) {
                            $indicators[] = Indicator::make('Rentals to: ' . Carbon::parse($data['start_date_to'])->format('M j, Y'))
                                ->removeField('start_date_to');
                        }

                        return $indicators;
                    }),

                // Financial Filters
                Filter::make('amount_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('min_amount')
                                    ->label('Min Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00'),
                                TextInput::make('max_amount')
                                    ->label('Max Amount')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('999.99'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['min_amount'] ?? null) {
                            $indicators[] = Indicator::make('Min: RM ' . number_format($data['min_amount'], 2))
                                ->removeField('min_amount');
                        }

                        if ($data['max_amount'] ?? null) {
                            $indicators[] = Indicator::make('Max: RM ' . number_format($data['max_amount'], 2))
                                ->removeField('max_amount');
                        }

                        return $indicators;
                    }),

                // Additional Filters
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'bank_transfer' => 'Bank Transfer',
                        'e_wallet' => 'E-Wallet',
                        'paypal' => 'PayPal',
                        'stripe' => 'Stripe',
                    ])
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('has_special_requests')
                    ->label('Has Special Requests')
                    ->placeholder('All bookings')
                    ->trueLabel('With special requests')
                    ->falseLabel('Without special requests')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('special_requests')->where('special_requests', '!=', ''),
                        false: fn (Builder $query) => $query->whereNull('special_requests')->orWhere('special_requests', '=', ''),
                    ),

                Filter::make('created_this_month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month))
                    ->label('Created This Month'),

                TrashedFilter::make(),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->persistFiltersInSession()
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->tooltip('View booking details'),

                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->tooltip('Edit booking'),

                Action::make('confirm')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Booking')
                    ->modalSubheading('This will mark the booking as confirmed. The customer will be notified.')
                    ->visible(fn ($record): bool => $record->status === BookingStatus::PENDING)
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::CONFIRMED]))
                    ->after(fn ($record) => \Filament\Notifications\Notification::make()
                        ->title('Booking confirmed successfully')
                        ->success()
                        ->send()),

                Action::make('start_rental')
                    ->icon('heroicon-m-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Start Rental')
                    ->modalSubheading('This will mark the booking as ongoing. The rental period begins now.')
                    ->visible(fn ($record): bool => $record->status === BookingStatus::CONFIRMED)
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::ONGOING])),

                Action::make('complete')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Rental')
                    ->modalSubheading('This will mark the booking as completed. The vehicle has been returned.')
                    ->visible(fn ($record): bool => $record->status === BookingStatus::ONGOING)
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::COMPLETED])),

                Action::make('cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Booking')
                    ->modalSubheading('This will cancel the booking. This action may affect payments and should be done carefully.')
                    ->visible(fn ($record): bool => in_array($record->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED]))
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::CANCELLED])),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Booking')
                    ->modalSubheading('Are you sure you want to delete this booking? This action cannot be undone.')
                    ->modalIcon('heroicon-o-trash'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm_multiple')
                        ->label('Confirm Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Multiple Bookings')
                        ->modalSubheading('This will confirm all selected pending bookings.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $confirmed = $records->where('status', BookingStatus::PENDING)->count();
                            $records->where('status', BookingStatus::PENDING)->each(fn ($record) => $record->update(['status' => BookingStatus::CONFIRMED]));

                            \Filament\Notifications\Notification::make()
                                ->title("Confirmed {$confirmed} bookings")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('cancel_multiple')
                        ->label('Cancel Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Multiple Bookings')
                        ->modalSubheading('This will cancel all selected bookings. Be careful with this action.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $cancelled = $records->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])->count();
                            $records->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])->each(fn ($record) => $record->update(['status' => BookingStatus::CANCELLED]));

                            \Filament\Notifications\Notification::make()
                                ->title("Cancelled {$cancelled} bookings")
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s') // Auto-refresh every 30 seconds
            ->persistSearchInSession()
            ->persistSortInSession();
    }
}
