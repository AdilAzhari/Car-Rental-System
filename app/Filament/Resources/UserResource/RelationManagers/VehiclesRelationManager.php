<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

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
        return $schema
            ->schema([
                // We'll keep this simple since the main vehicle form is comprehensive
            ]);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/car-placeholder.jpg'))
                    ->size(50),

                TextColumn::make('make')
                    ->label('Make')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                TextColumn::make('license_plate')
                    ->label('License Plate')
                    ->searchable()
                    ->fontFamily('mono'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'maintenance',
                        'gray' => 'archived',
                    ]),

                TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn (): string => route('filament.admin.resources.vehicles.create', [
                        'owner_id' => $this->ownerRecord->id
                    ])),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.vehicles.view', $record)),
                EditAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.vehicles.edit', $record)),
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