<?php

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Resources\Bookings\Schemas\BookingInfolist;
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
        return BookingForm::configure($schema);
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
                    ->label(__('resources.customer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('resources.start_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('resources.end_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('duration')
                    ->label(__('resources.duration'))
                    ->state(function ($record): string {
                        $days = \Carbon\Carbon::parse($record->start_date)
                            ->diffInDays(\Carbon\Carbon::parse($record->end_date)) + 1;

                        return $days.' day'.($days !== 1 ? 's' : '');
                    }),

                BadgeColumn::make('status')
                    ->label(__('resources.status'))
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'success' => 'active',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => $state instanceof BookingStatus ? $state->label() : ($state ?? 'Unknown')),

                TextColumn::make('total_amount')
                    ->label(__('resources.amount'))
                    ->money(config('app.currency', 'MYR'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('resources.booked_on'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('resources.create_booking'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['vehicle_id'] = $this->ownerRecord->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn ($record): string => __('resources.booking').' #BK-'.$record->id)
                    ->infolist(fn (): array => BookingInfolist::configure(new \Filament\Schemas\Schema)->getComponents()),
                EditAction::make()
                    ->modalHeading(fn ($record): string => __('resources.edit_booking').' #BK-'.$record->id),
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
