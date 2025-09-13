<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\BookingStatus;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * @throws Exception
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // We'll keep this simple since the main booking form is comprehensive
            ]);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.booking_id'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('vehicle.make')
                    ->label(__('resources.vehicle'))
                    ->formatStateUsing(fn ($record): string => "{$record->vehicle->make} {$record->vehicle->model}")
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label(__('resources.start_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('resources.end_date'))
                    ->dateTime()
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label(__('resources.status'))
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => BookingStatus::tryFrom($state)?->label() ?? $state),

                TextColumn::make('total_amount')
                    ->label(__('resources.amount'))
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('resources.booked_on'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn (): string => route('filament.admin.resources.bookings.create', [
                        'renter_id' => $this->ownerRecord->id,
                    ])),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.bookings.view', $record)),
                EditAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.bookings.edit', $record)),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
