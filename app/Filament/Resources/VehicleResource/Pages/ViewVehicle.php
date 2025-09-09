<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BooleanEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\KeyValueEntry;

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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Vehicle Gallery')
                    ->icon('heroicon-m-photo')
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label('Featured Image')
                            ->height(300)
                            ->columnSpanFull(),

                        ImageEntry::make('gallery_images')
                            ->label('Gallery')
                            ->height(150)
                            ->columnSpanFull(),
                    ]),

                Section::make('Basic Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('make')
                                    ->label('Make')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('model')
                                    ->label('Model')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('year')
                                    ->label('Year')
                                    ->badge()
                                    ->color('primary'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('license_plate')
                                    ->label('License Plate')
                                    ->fontFamily('mono')
                                    ->copyable(),

                                TextEntry::make('vin')
                                    ->label('VIN Number')
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->placeholder('Not provided'),
                            ]),
                    ]),

                Section::make('Categories & Specifications')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('category')
                                    ->label('Category')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'luxury' => 'danger',
                                        'sports' => 'warning',
                                        'suv' => 'info',
                                        default => 'success',
                                    }),

                                TextEntry::make('transmission')
                                    ->label('Transmission')
                                    ->badge(),

                                TextEntry::make('fuel_type')
                                    ->label('Fuel Type')
                                    ->badge(),

                                TextEntry::make('seating_capacity')
                                    ->label('Seats')
                                    ->suffix(' passengers')
                                    ->icon('heroicon-m-user-group'),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextEntry::make('doors')
                                    ->label('Doors')
                                    ->placeholder('Not specified'),

                                TextEntry::make('engine_size')
                                    ->label('Engine Size')
                                    ->suffix('L')
                                    ->placeholder('Not specified'),

                                TextEntry::make('mileage')
                                    ->label('Mileage')
                                    ->suffix(' km')
                                    ->placeholder('Not specified'),

                                TextEntry::make('daily_rate')
                                    ->label('Daily Rate')
                                    ->money('MYR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('Ownership & Status')
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('owner.name')
                                    ->label('Owner')
                                    ->weight('medium'),

                                TextEntry::make('owner.email')
                                    ->label('Owner Email')
                                    ->copyable(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'published' => 'success',
                                        'draft' => 'warning',
                                        'maintenance' => 'danger',
                                        'archived' => 'gray',
                                        default => 'primary',
                                    }),

                                BooleanEntry::make('is_available')
                                    ->label('Available for Rent')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                BooleanEntry::make('insurance_included')
                                    ->label('Insurance Included')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),
                    ]),

                Section::make('Location Information')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('location')
                                    ->label('Current Location')
                                    ->placeholder('Not specified'),

                                TextEntry::make('pickup_location')
                                    ->label('Pickup Location')
                                    ->placeholder('Not specified'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Features & Specifications')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        KeyValueEntry::make('features')
                            ->label('Vehicle Features')
                            ->columnSpanFull(),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description available')
                            ->columnSpanFull(),

                        TextEntry::make('terms_and_conditions')
                            ->label('Terms & Conditions')
                            ->placeholder('No specific terms')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Statistics')
                    ->icon('heroicon-m-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('bookings_count')
                                    ->label('Total Bookings')
                                    ->state(fn ($record) => $record->bookings->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('total_revenue')
                                    ->label('Total Revenue')
                                    ->state(fn ($record) => $record->bookings->where('status', 'completed')->sum('total_amount'))
                                    ->money('MYR')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('average_rating')
                                    ->label('Average Rating')
                                    ->state(fn ($record) => $record->reviews->avg('rating') ? round($record->reviews->avg('rating'), 1) . '/5' : 'No reviews')
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('System Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Added to Fleet')
                                    ->dateTime(),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}