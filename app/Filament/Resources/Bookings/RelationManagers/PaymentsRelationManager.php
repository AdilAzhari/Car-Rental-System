<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'transaction_id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('amount')
                    ->label(__('resources.amount'))
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix('RM')
                    ->placeholder('0.00'),

                Select::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->options(collect(PaymentMethod::cases())->pluck('label', 'value'))
                    ->required()
                    ->default(PaymentMethod::CASH->value),

                Select::make('payment_status')
                    ->label(__('resources.payment_status'))
                    ->options(collect(PaymentStatus::cases())->pluck('label', 'value'))
                    ->required()
                    ->default(PaymentStatus::PENDING->value),

                TextInput::make('transaction_id')
                    ->label(__('resources.transaction_id'))
                    ->placeholder(__('resources.transaction_id_placeholder'))
                    ->helperText(__('resources.leave_empty_for_cash')),

                DateTimePicker::make('processed_at')
                    ->label(__('resources.processed_at'))
                    ->default(now())
                    ->placeholder(__('resources.select_date_time')),

                TextInput::make('refund_amount')
                    ->label(__('resources.refund_amount'))
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('RM')
                    ->placeholder('0.00'),

                DateTimePicker::make('refunded_at')
                    ->label(__('resources.refunded_at'))
                    ->placeholder(__('resources.select_date_time')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.payment_id'))
                    ->sortable()
                    ->prefix('PAY-'),

                TextColumn::make('amount')
                    ->label(__('resources.amount'))
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : $state)
                    ->color(fn ($state): string => match ($state) {
                        PaymentMethod::CASH => 'success',
                        PaymentMethod::VISA, PaymentMethod::CREDIT => 'primary',
                        PaymentMethod::BANK_TRANSFER => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->label(__('resources.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : $state)
                    ->color(fn ($state): string => match ($state) {
                        PaymentStatus::CONFIRMED => 'success',
                        PaymentStatus::PENDING => 'warning',
                        PaymentStatus::FAILED, PaymentStatus::CANCELLED => 'danger',
                        PaymentStatus::REFUNDED => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('transaction_id')
                    ->label(__('resources.transaction_id'))
                    ->searchable()
                    ->placeholder(__('resources.manual_payment'))
                    ->copyable(),

                TextColumn::make('processed_at')
                    ->label(__('resources.processed_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('resources.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('resources.add_payment'))
                    ->icon('heroicon-m-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Generate transaction ID for cash payments if not provided
                        if ($data['payment_method'] === PaymentMethod::CASH->value && empty($data['transaction_id'])) {
                            $data['transaction_id'] = 'CASH-'.strtoupper(uniqid());
                        }

                        return $data;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('resources.no_payments'))
            ->emptyStateDescription(__('resources.no_payments_description'))
            ->emptyStateIcon('heroicon-o-credit-card')
            ->defaultSort('created_at', 'desc');
    }
}
