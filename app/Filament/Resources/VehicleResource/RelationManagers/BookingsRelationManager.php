<?php

namespace App\Filament\Resources\VehicleResource\RelationManagers;

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
                // Keep simple - main booking form is comprehensive
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
                    ->label('Booking #')
                    ->sortable()
                    ->searchable()
                    ->prefix('BK-'),

                TextColumn::make('renter.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record) {
                        $days = \Carbon\Carbon::parse($record->start_date)
                            ->diffInDays(\Carbon\Carbon::parse($record->end_date)) + 1;
                        return $days . ' day' . ($days !== 1 ? 's' : '');
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => BookingStatus::tryFrom($state)?->label() ?? $state),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Booked On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn (): string => route('filament.admin.resources.bookings.create', [
                        'vehicle_id' => $this->ownerRecord->id
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