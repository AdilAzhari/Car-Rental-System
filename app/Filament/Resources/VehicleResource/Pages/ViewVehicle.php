<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;

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
                        FileUpload::make('featured_image')
                            ->label('Featured Image')
                            ->disabled()
                            ->columnSpanFull(),

                        FileUpload::make('gallery_images')
                            ->label('Gallery')
                            ->disabled()
                            ->multiple()
                            ->columnSpanFull(),
                    ]),

                Section::make('Basic Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('make')
                                    ->label('Make')
                                    ->disabled(),

                                TextInput::make('model')
                                    ->label('Model')
                                    ->disabled(),

                                TextInput::make('year')
                                    ->label('Year')
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('license_plate')
                                    ->label('License Plate')
                                    ->disabled(),

                                TextInput::make('vin')
                                    ->label('VIN Number')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Categories & Specifications')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('category')
                                    ->label('Category')
                                    ->disabled(),

                                TextInput::make('transmission')
                                    ->label('Transmission')
                                    ->disabled(),

                                TextInput::make('fuel_type')
                                    ->label('Fuel Type')
                                    ->disabled(),

                                TextInput::make('seating_capacity')
                                    ->label('Seats')
                                    ->disabled(),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('doors')
                                    ->label('Doors')
                                    ->disabled(),

                                TextInput::make('engine_size')
                                    ->label('Engine Size (L)')
                                    ->disabled(),

                                TextInput::make('mileage')
                                    ->label('Mileage (km)')
                                    ->disabled(),

                                TextInput::make('daily_rate')
                                    ->label('Daily Rate')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Ownership & Status')
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),

                                Toggle::make('is_available')
                                    ->label('Available for Rent')
                                    ->disabled(),

                                Toggle::make('insurance_included')
                                    ->label('Insurance Included')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Location Information')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('location')
                                    ->label('Current Location')
                                    ->disabled(),

                                TextInput::make('pickup_location')
                                    ->label('Pickup Location')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Features & Specifications')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        KeyValue::make('features')
                            ->label('Vehicle Features')
                            ->disabled()
                            ->columnSpanFull(),

                        TextInput::make('description')
                            ->label('Description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}