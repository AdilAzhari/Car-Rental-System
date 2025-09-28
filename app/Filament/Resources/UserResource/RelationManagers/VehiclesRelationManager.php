<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource\Schemas\VehicleForm;
use App\Filament\Resources\VehicleResource\Schemas\VehicleInfolist;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $recordTitleAttribute = 'make';

    /**
     * @throws Exception
     */
    public function form(Schema $schema): Schema
    {
        return VehicleForm::configure($schema);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('resources.image'))
                    ->circular()
                    ->defaultImageUrl(url('/images/car-placeholder.jpg'))
                    ->size(50)
                    ->getStateUsing(fn ($record) => $record->featured_image ?: '/images/car-placeholder.jpg'),

                TextColumn::make('make')
                    ->label(__('resources.make'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->label(__('resources.model'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label(__('resources.year'))
                    ->sortable(),

                TextColumn::make('license_plate')
                    ->label(__('resources.license_plate'))
                    ->searchable()
                    ->fontFamily('mono'),

                BadgeColumn::make('status')
                    ->label(__('resources.status'))
                    ->getStateUsing(fn ($record) => $record->status instanceof VehicleStatus ? $record->status->value : (string) $record->status)
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'maintenance',
                        'gray' => 'archived',
                    ]),

                TextColumn::make('daily_rate')
                    ->label(__('resources.daily_rate'))
                    ->money(config('app.currency', 'MYR'))
                    ->sortable(),

                TextColumn::make('bookings_count')
                    ->label(__('resources.bookings'))
                    ->counts('bookings')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label(__('resources.added'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading(__('resources.create_vehicle'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['owner_id'] = $this->ownerRecord->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn ($record): string => __('resources.vehicle').': '.$record->make.' '.$record->model)
                    ->infolist(fn (): array => VehicleInfolist::configure(new \Filament\Schemas\Schema)->getComponents()),
                EditAction::make()
                    ->modalHeading(fn ($record): string => __('resources.edit_vehicle').': '.$record->make.' '.$record->model),
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
