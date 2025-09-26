<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use App\Services\TrafficViolationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
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
            Action::make('check_violations')
                ->label('Check Violations')
                ->icon('heroicon-m-shield-exclamation')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Check Traffic Violations')
                ->modalDescription('This will check for traffic violations via SMS and update the vehicle record. Continue?')
                ->modalSubmitActionLabel('Check Now')
                ->action(function (TrafficViolationService $trafficViolationService): void {
                    $model = $this->getRecord();

                    // Clear cache to force new check
                    $trafficViolationService->clearCache($model->plate_number);

                    // Check violations
                    $violationData = $trafficViolationService->checkVehicleViolations($model);

                    // Update vehicle record
                    $trafficViolationService->updateVehicleViolations($model, $violationData);

                    // Show notification
                    $violationCount = count($violationData['violations']);
                    $totalFines = $violationData['total_fines_amount'];

                    if ($violationData['has_violations']) {
                        Notification::make()
                            ->title('Violations Found')
                            ->body("Found {$violationCount} violation(s) with total fines of RM ".number_format($totalFines, 2))
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No Violations Found')
                            ->body("Vehicle {$model->plate_number} has a clean traffic record.")
                            ->success()
                            ->send();
                    }

                    // Refresh the page to show updated data
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $model]));
                }),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    #[\Override]
    public function getView(): string
    {
        return 'filament.pages.vehicle-view';
    }

    #[\Override]
    public function getTitle(): string
    {
        return $this->getRecord()->make.' '.$this->getRecord()->model.' ('.$this->getRecord()->year.')';
    }

    #[\Override]
    public function getSubheading(): ?string
    {
        return __('resources.vehicle_details');
    }

    #[\Override]
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('resources.gallery_images'))
                    ->icon('heroicon-m-photo')
                    ->schema([
                        ImageEntry::make('featured_image')
                            ->label(__('resources.featured_image'))
                            ->size(600) // Make featured image much larger
                            ->square(false) // Allow rectangular images
                            ->extraImgAttributes([
                                'class' => 'rounded-lg shadow-lg transition-all duration-300',
                            ])
                            ->columnSpanFull(),

                        ImageEntry::make('gallery_images')
                            ->label(__('resources.gallery'))
                            ->stacked(false) // Show images in a grid instead of stacked
                            ->size(200) // Medium size for gallery thumbnails
                            ->square()
                            ->extraImgAttributes([
                                'class' => 'rounded-lg shadow-md transition-all duration-300',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make(__('resources.basic_information'))
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

                Section::make(__('resources.vehicle_categories_description'))
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
                                    ->money(config('app.currency', 'MYR')),
                            ]),
                    ]),

                Section::make(__('resources.ownership_and_status'))
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

                Section::make(__('resources.location_information'))
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

                Section::make(__('resources.features_specifications'))
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

                Section::make(__('resources.traffic_violations'))
                    ->icon('heroicon-m-shield-exclamation')
                    ->description(__('resources.traffic_violations_description'))
                    ->schema([
                        ViewEntry::make('traffic_violations_display')
                            ->label('')
                            ->view('filament.components.traffic-violations-display')
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
