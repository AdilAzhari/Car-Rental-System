<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentRelationManager extends RelationManager
{
    protected static string $relationship = 'payment';

    /**
     * @throws Exception
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('amount')
                    ->label(__('resources.amount'))
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01),

                Select::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($case): array => [$case->value => $case->label()]))
                    ->required(),

                Select::make('payment_status')
                    ->label(__('resources.payment_status'))
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn ($case): array => [$case->value => $case->label()]))
                    ->required(),

                TextInput::make('transaction_id')
                    ->label(__('resources.transaction_id'))
                    ->maxLength(255),

                DateTimePicker::make('processed_at')
                    ->label(__('resources.processed_at')),

                TextInput::make('refund_amount')
                    ->label(__('resources.refund_amount'))
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01),

                DateTimePicker::make('refunded_at')
                    ->label(__('resources.refunded_at')),

                Textarea::make('gateway_response')
                    ->label(__('resources.gateway_response'))
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                TextColumn::make('amount')
                    ->label(__('resources.amount'))
                    ->money(config('app.currency', 'USD'))
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('resources.payment_method'))
                    ->formatStateUsing(fn ($state) => PaymentMethod::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                BadgeColumn::make('payment_status')
                    ->label(__('resources.status'))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'failed',
                        'info' => 'refunded',
                        'secondary' => 'cancelled',
                        'primary' => 'processing',
                    ])
                    ->formatStateUsing(fn ($state) => PaymentStatus::tryFrom($state)?->label() ?? $state),

                TextColumn::make('transaction_id')
                    ->label(__('resources.transaction_id'))
                    ->limit(20)
                    ->searchable(),

                TextColumn::make('processed_at')
                    ->label(__('resources.processed_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('refund_amount')
                    ->label(__('resources.refund_amount'))
                    ->money(config('app.currency', 'USD'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
