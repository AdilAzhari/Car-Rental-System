<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewVehicle extends ViewRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Vehicle Gallery')
                    ->icon('heroicon-m-photo')
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label(__('resources.featured_image'))
                            ->columnSpanFull(),

                        ImageEntry::make('gallery_images')
                            ->label(__('resources.gallery'))
                            ->stacked()
                            ->columnSpanFull(),
                    ]),

                Section::make('Basic Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('make')
                                    ->label(__('resources.make')),

                                TextEntry::make('model')
                                    ->label(__('resources.model')),

                                TextEntry::make('year')
                                    ->label(__('resources.year')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('plate_number')
                                    ->label(__('resources.license_plate'))
                                    ->copyable(),

                                TextEntry::make('vin')
                                    ->label(__('resources.vin_number'))
                                    ->copyable(),
                            ]),
                    ]),

                Section::make('Categories & Specifications')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('category')
                                    ->label(__('resources.category'))
                                    ->badge(),

                                TextEntry::make('transmission')
                                    ->label(__('resources.transmission'))
                                    ->badge(),

                                TextEntry::make('fuel_type')
                                    ->label(__('resources.fuel_type'))
                                    ->badge(),

                                TextEntry::make('seats')
                                    ->label(__('resources.seats'))
                                    ->icon('heroicon-m-user-group'),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextEntry::make('doors')
                                    ->label(__('resources.doors')),

                                TextEntry::make('engine_size')
                                    ->label('Engine Size (L)')
                                    ->suffix(' L'),

                                TextEntry::make('mileage')
                                    ->label('Mileage (km)')
                                    ->numeric()
                                    ->suffix(' km'),

                                TextEntry::make('daily_rate')
                                    ->label(__('resources.daily_rate'))
                                    ->money('MYR'),
                            ]),
                    ]),

                Section::make('Ownership & Status')
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label(__('resources.status'))
                                    ->badge()
                                    ->color(fn ($state) => $state?->color()),

                                IconEntry::make('is_available')
                                    ->label(__('resources.available_for_rent'))
                                    ->boolean(),

                                IconEntry::make('insurance_included')
                                    ->label(__('resources.insurance_included'))
                                    ->boolean(),
                            ]),
                    ]),

                Section::make('Location Information')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('location')
                                    ->label(__('resources.current_location'))
                                    ->icon('heroicon-m-map-pin'),

                                TextEntry::make('pickup_location')
                                    ->label(__('resources.pickup_location'))
                                    ->icon('heroicon-m-map'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Features & Specifications')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        KeyValueEntry::make('features')
                            ->label(__('resources.vehicle_features'))
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->label(__('resources.description'))
                            ->columnSpanFull()
                            ->markdown(),
                    ])
                    ->collapsible(),
            ]);
    }
}
