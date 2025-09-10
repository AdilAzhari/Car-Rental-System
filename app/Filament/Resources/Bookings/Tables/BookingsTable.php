<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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
            ->query(fn () => Booking::query()->with(['renter', 'vehicle', 'payment']))
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.id'))
                    ->prefix('#')
                    ->sortable()
                    ->size('sm')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->copyMessage(__('resources.booking_id_copied'))
                    ->tooltip(__('resources.click_to_copy')),

                TextColumn::make('renter.name')
                    ->label(__('resources.customer'))
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->renter?->email)
                    ->icon('heroicon-m-user')
                    ->wrap(),

                TextColumn::make('vehicle.make')
                    ->label(__('resources.vehicle'))
                    ->formatStateUsing(fn ($record) => $record->vehicle
                        ? "{$record->vehicle->make} {$record->vehicle->model} ({$record->vehicle->year})"
                        : __('resources.na'))
                    ->searchable(['make', 'model', 'plate_number'])
                    ->sortable(['make', 'model'])
                    ->description(fn ($record) => $record->vehicle?->plate_number)
                    ->icon('heroicon-m-truck')
                    ->weight('medium')
                    ->wrap(),

                TextColumn::make('booking_period')
                    ->label(__('resources.rental_period'))
                    ->formatStateUsing(function ($record) {
                        $start = Carbon::parse($record->start_date);
                        $end = Carbon::parse($record->end_date);
                        $days = $end->diffInDays($start) + 1;

                        return $start->format('M j') . ' - ' . $end->format('M j, Y') . " ($days" . __('resources.days_short') . ")";
                    })
                    ->description(fn ($record) => Carbon::parse($record->start_date)->diffForHumans())
                    ->sortable('start_date')
                    ->icon('heroicon-m-calendar-days')
                    ->wrap(),

                TextColumn::make('total_amount')
                    ->label(__('resources.amount'))
                    ->money('MYR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->description(fn ($record) => $record->deposit_amount
                        ? __('resources.deposit') . ': RM ' . number_format($record->deposit_amount, 2)
                        : null),

                TextColumn::make('status')
                    ->label(__('resources.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof BookingStatus
                        ? $state->label()
                        : BookingStatus::from($state)->label())
                    ->color(fn ($state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                        BookingStatus::PENDING => 'warning',
                        BookingStatus::CONFIRMED => 'info',
                        BookingStatus::ONGOING => 'primary',
                        BookingStatus::COMPLETED => 'success',
                        BookingStatus::CANCELLED => 'danger'
                    })
                    ->icon(fn ($state): string => match ($state instanceof BookingStatus ? $state : BookingStatus::from($state)) {
                        BookingStatus::PENDING => 'heroicon-m-clock',
                        BookingStatus::CONFIRMED => 'heroicon-m-check-circle',
                        BookingStatus::ONGOING => 'heroicon-m-play',
                        BookingStatus::COMPLETED => 'heroicon-m-check-badge',
                        BookingStatus::CANCELLED => 'heroicon-m-x-circle',
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label(__('resources.payment'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'unpaid' => __('resources.unpaid'),
                        'paid' => __('resources.paid'),
                        'refunded' => __('resources.refunded'),
                        default => ucfirst($state),
                    })
                    ->color(fn ($state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        'refunded' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn ($state): string => match ($state) {
                        'unpaid' => 'heroicon-m-exclamation-circle',
                        'paid' => 'heroicon-m-check-circle',
                        'refunded' => 'heroicon-m-arrow-uturn-left',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable()
                    ->description(fn ($record) => $record->payment_method
                        ? __('enums.payment_method.' . $record->payment_method)
                        : __('resources.no_method')),

                TextColumn::make('created_at')
                    ->label(__('resources.created'))
                    ->dateTime('M j, H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => Carbon::parse($record->created_at)->format('Y-m-d'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Quick Status Filters
                SelectFilter::make('status')
                    ->label(__('resources.booking_status'))
                    ->options(BookingStatus::class)
                    ->multiple()
                    ->preload(),

                SelectFilter::make('payment_status')
                    ->label(__('resources.payment_status'))
                    ->options([
                        'unpaid' => __('resources.unpaid'),
                        'paid' => __('resources.paid'),
                        'refunded' => __('resources.refunded'),
                    ])
                    ->multiple()
                    ->preload(),

                // Advanced Date Filters
                Filter::make('date_range')
                    ->form([
                        Grid::make()
                            ->schema([
                                DatePicker::make('start_date_from')
                                    ->label(__('resources.rental_starts_from'))
                                    ->placeholder(__('resources.select_start_date')),
                                DatePicker::make('start_date_to')
                                    ->label(__('resources.rental_starts_to'))
                                    ->placeholder(__('resources.select_end_date')),
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
                            $indicators[] = Indicator::make(__('resources.rentals_from') . ': ' . Carbon::parse($data['start_date_from'])->format('M j, Y'))
                                ->removeField('start_date_from');
                        }

                        if ($data['start_date_to'] ?? null) {
                            $indicators[] = Indicator::make(__('resources.rentals_to') . ': ' . Carbon::parse($data['start_date_to'])->format('M j, Y'))
                                ->removeField('start_date_to');
                        }

                        return $indicators;
                    }),

                // Financial Filters
                Filter::make('amount_range')
                    ->form([
                        Grid::make()
                            ->schema([
                                TextInput::make('min_amount')
                                    ->label(__('resources.min_amount'))
                                    ->numeric()
                                    ->prefix('RM')
                                    ->placeholder('0.00'),
                                TextInput::make('max_amount')
                                    ->label(__('resources.max_amount'))
                                    ->numeric()
                                    ->prefix('RM')
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
                            $indicators[] = Indicator::make(__('resources.min') . ': RM ' . number_format($data['min_amount'], 2))
                                ->removeField('min_amount');
                        }

                        if ($data['max_amount'] ?? null) {
                            $indicators[] = Indicator::make(__('resources.max') . ': RM ' . number_format($data['max_amount'], 2))
                                ->removeField('max_amount');
                        }

                        return $indicators;
                    }),

                // Additional Filters
                SelectFilter::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->options([
                        'cash' => __('enums.payment_method.cash'),
                        'credit_card' => __('enums.payment_method.credit_card'),
                        'debit_card' => __('enums.payment_method.debit_card'),
                        'bank_transfer' => __('enums.payment_method.bank_transfer'),
                        'e_wallet' => __('enums.payment_method.e_wallet'),
                        'paypal' => __('enums.payment_method.paypal'),
                        'stripe' => __('enums.payment_method.stripe'),
                    ])
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('has_special_requests')
                    ->label(__('resources.has_special_requests'))
                    ->placeholder(__('resources.all_bookings'))
                    ->trueLabel(__('resources.with_special_requests'))
                    ->falseLabel(__('resources.without_special_requests'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('special_requests')->where('special_requests', '!=', ''),
                        false: fn (Builder $query) => $query->whereNull('special_requests')->orWhere('special_requests', '=', ''),
                    ),

                Filter::make('created_this_month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month))
                    ->label(__('resources.created_this_month')),

                TrashedFilter::make(),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->persistFiltersInSession()
            ->recordActions([
                ViewAction::make()
                    ->label(__('forms.actions.view'))
                    ->icon('heroicon-m-eye')
                    ->tooltip(__('resources.view_booking_details')),

                EditAction::make()
                    ->label(__('forms.actions.edit'))
                    ->icon('heroicon-m-pencil-square')
                    ->tooltip(__('resources.edit_booking')),

                Action::make('confirm')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('resources.confirm_booking'))
                    ->modalSubheading(__('resources.confirm_booking_description'))
                    ->visible(fn ($record): bool => $record->status === BookingStatus::PENDING)
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::CONFIRMED]))
                    ->after(fn ($record) => Notification::make()
                        ->title('Booking confirmed successfully')
                        ->success()
                        ->send()),

                Action::make('start_rental')
                    ->icon('heroicon-m-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading(__('resources.start_rental'))
                    ->modalSubheading(__('resources.start_rental_description'))
                    ->visible(fn ($record): bool => $record->status === BookingStatus::CONFIRMED)
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::ONGOING])),

                Action::make('complete')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('resources.complete_rental'))
                    ->modalSubheading(__('resources.complete_rental_description'))
                    ->visible(fn ($record): bool => $record->status === BookingStatus::ONGOING)
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::COMPLETED])),

                Action::make('cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('resources.cancel_booking'))
                    ->modalSubheading(__('resources.cancel_booking_description'))
                    ->visible(fn ($record): bool => in_array($record->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED]))
                    ->action(fn ($record) => $record->update(['status' => BookingStatus::CANCELLED])),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('resources.delete_booking'))
                    ->modalSubheading(__('resources.delete_booking_description'))
                    ->modalIcon('heroicon-o-trash'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('confirm_multiple')
                        ->label(__('resources.confirm_selected'))
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('resources.confirm_multiple_bookings'))
                        ->modalSubheading(__('resources.confirm_multiple_bookings_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $confirmed = $records->where('status', BookingStatus::PENDING)->count();
                            $records->where('status', BookingStatus::PENDING)->each(fn ($record) => $record->update(['status' => BookingStatus::CONFIRMED]));

                            Notification::make()
                                ->title("Confirmed $confirmed bookings")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('cancel_multiple')
                        ->label(__('resources.cancel_selected'))
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('resources.cancel_multiple_bookings'))
                        ->modalSubheading(__('resources.cancel_multiple_bookings_description'))
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $cancelled = $records->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])->count();
                            $records->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])->each(fn ($record) => $record->update(['status' => BookingStatus::CANCELLED]));

                            Notification::make()
                                ->title("Cancelled $cancelled bookings")
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
