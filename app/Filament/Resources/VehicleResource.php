<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use UnitEnum;
use BackedEnum;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static UnitEnum|string|null $navigationGroup = 'Fleet Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('resources.vehicles');
    }

    public static function getModelLabel(): string
    {
        return __('resources.vehicle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.vehicles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources.vehicle_management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('resources.vehicle_information'))
                    ->description(__('resources.basic_information'))
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('make')
                                    ->label(__('resources.make'))
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder(__('resources.make_placeholder')),

                                TextInput::make('model')
                                    ->label(__('resources.model'))
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder(__('resources.model_placeholder')),

                                TextInput::make('year')
                                    ->label(__('resources.year'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(now()->year + 1)
                                    ->placeholder(__('resources.year_placeholder')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('plate_number')
                                    ->label(__('resources.license_plate'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    ->placeholder(__('resources.license_plate_placeholder'))
                                    ->suffixIcon('heroicon-m-identification'),

                                TextInput::make('vin')
                                    ->label(__('resources.vin_number'))
                                    ->maxLength(17)
                                    ->placeholder(__('resources.vin_placeholder'))
                                    ->helperText(__('resources.vin_helper')),
                            ]),
                    ]),

                Section::make(__('resources.vehicle_categories'))
                    ->description(__('resources.vehicle_categories_description'))
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('category')
                                    ->label(__('resources.category'))
                                    ->options([
                                        'economy' => __('enums.vehicle_category.economy'),
                                        'compact' => __('enums.vehicle_category.compact'),
                                        'midsize' => __('enums.vehicle_category.midsize'),
                                        'fullsize' => __('enums.vehicle_category.fullsize'),
                                        'luxury' => __('enums.vehicle_category.luxury'),
                                        'suv' => __('enums.vehicle_category.suv'),
                                        'minivan' => __('enums.vehicle_category.minivan'),
                                        'pickup' => __('enums.vehicle_category.pickup'),
                                        'convertible' => __('enums.vehicle_category.convertible'),
                                        'sports' => __('enums.vehicle_category.sports'),
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('transmission')
                                    ->label(__('resources.transmission'))
                                    ->options([
                                        'automatic' => __('enums.transmission.automatic'),
                                        'manual' => __('enums.transmission.manual'),
                                        'cvt' => __('enums.transmission.cvt'),
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('fuel_type')
                                    ->label(__('resources.fuel_type'))
                                    ->options([
                                        'petrol' => __('enums.fuel_type.petrol'),
                                        'diesel' => __('enums.fuel_type.diesel'),
                                        'hybrid' => __('enums.fuel_type.hybrid'),
                                        'electric' => __('enums.fuel_type.electric'),
                                        'lpg' => __('enums.fuel_type.lpg'),
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('seats')
                                    ->label(__('resources.seats'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(5)
                                    ->suffixIcon('heroicon-m-user-group'),

                                TextInput::make('color')
                                    ->label(__('resources.color'))
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder(__('resources.color_placeholder')),

                                TextInput::make('engine_size')
                                    ->label(__('resources.engine_size'))
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0.1)
                                    ->placeholder(__('resources.engine_size_placeholder')),

                                TextInput::make('mileage')
                                    ->label(__('resources.mileage_km'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder(__('resources.mileage_placeholder')),
                            ]),
                    ]),

                Section::make(__('resources.ownership_pricing'))
                    ->description(__('resources.ownership_pricing_description'))
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('owner_id')
                                    ->label(__('resources.vehicle_owner'))
                                    ->relationship('owner', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('email')->email()->required(),
                                        Select::make('role')->options(['owner' => __('resources.owner')])->default('owner'),
                                    ])
                                    ->placeholder(__('resources.select_or_create_owner')),

                                TextInput::make('daily_rate')
                                    ->label(__('resources.daily_rate'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('RM')
                                    ->step(0.01)
                                    ->placeholder(__('resources.daily_rate_placeholder')),
                            ]),
                    ]),

                Section::make(__('resources.vehicle_status_location'))
                    ->description(__('resources.vehicle_status_location_description'))
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->label(__('resources.status'))
                                    ->options([
                                        'draft' => __('resources.draft'),
                                        'published' => __('resources.published'),
                                        'maintenance' => __('resources.under_maintenance'),
                                        'archived' => __('resources.archived'),
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),

                                Toggle::make('is_available')
                                    ->label(__('resources.available_for_rent'))
                                    ->default(true)
                                    ->helperText(__('resources.can_customers_book')),

                                Toggle::make('insurance_included')
                                    ->label(__('resources.insurance_included'))
                                    ->default(true)
                                    ->helperText(__('resources.does_rental_include_insurance')),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('location')
                                    ->label(__('resources.current_location'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('resources.location_placeholder')),

                                TextInput::make('pickup_location')
                                    ->label(__('resources.pickup_location'))
                                    ->maxLength(255)
                                    ->placeholder(__('resources.pickup_location_placeholder')),

                                DatePicker::make('insurance_expiry')
                                    ->label(__('resources.insurance_expiry'))
                                    ->required()
                                    ->minDate(now())
                                    ->placeholder(__('resources.insurance_expiry_placeholder')),
                            ]),
                    ]),

                Section::make(__('resources.images_media'))
                    ->description(__('resources.images_media_description'))
                    ->icon('heroicon-m-photo')
                    ->schema([
                        FileUpload::make('featured_image')
                            ->label(__('resources.featured_image'))
                            ->image()
                            ->directory('vehicles/featured')
                            ->maxSize(5120)
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600),

                        FileUpload::make('gallery_images')
                            ->label(__('resources.gallery_images'))
                            ->multiple()
                            ->image()
                            ->directory('vehicles/gallery')
                            ->maxFiles(10)
                            ->maxSize(5120)
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->helperText(__('resources.gallery_images_helper')),

                        FileUpload::make('documents')
                            ->label(__('resources.documents'))
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->directory('vehicles/documents')
                            ->maxFiles(5)
                            ->helperText(__('resources.documents_helper')),
                    ])
                    ->collapsible(),

                Section::make(__('resources.features_specifications'))
                    ->description(__('resources.features_specifications_description'))
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        KeyValue::make('features')
                            ->label(__('resources.vehicle_features'))
                            ->keyLabel(__('resources.feature'))
                            ->valueLabel(__('resources.details'))
                            ->default([
                                __('resources.air_conditioning') => __('resources.yes'),
                                __('resources.bluetooth') => __('resources.yes'),
                                __('resources.gps_navigation') => __('resources.yes'),
                            ]),

                        Textarea::make('description')
                            ->label(__('resources.description'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder(__('resources.description_placeholder'))
                            ->columnSpanFull(),

                        Textarea::make('terms_and_conditions')
                            ->label(__('resources.terms_conditions'))
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder(__('resources.terms_conditions_placeholder'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make(__('resources.traffic_violations'))
                    ->description(__('resources.traffic_violations_description'))
                    ->icon('heroicon-m-exclamation-triangle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('has_pending_violations')
                                    ->label(__('resources.has_pending_violations'))
                                    ->helperText(__('resources.pending_violations_helper'))
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('total_violations_count')
                                    ->label(__('resources.total_violations_count'))
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffixIcon('heroicon-m-exclamation-circle'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_fines_amount')
                                    ->label(__('resources.total_fines_amount'))
                                    ->numeric()
                                    ->prefix('RM')
                                    ->step(0.01)
                                    ->default(0.00)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffixIcon('heroicon-m-currency-dollar'),

                                DateTimePicker::make('violations_last_checked')
                                    ->label(__('resources.last_checked'))
                                    ->displayFormat('d M Y H:i')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffixIcon('heroicon-m-clock'),
                            ]),

                        // Traffic Violations Details View
                        ViewField::make('traffic_violations_display')
                            ->label(__('resources.violation_details'))
                            ->view('filament.components.traffic-violations-display')
                            ->visible(fn ($record) => $record && $record->traffic_violations && count($record->traffic_violations) > 0)
                            ->columnSpanFull(),

                        // API Integration Actions
                        Grid::make(1)
                            ->schema([
                                Placeholder::make('api_integration_info')
                                    ->label(__('resources.api_integration'))
                                    ->content(fn (): string => __('resources.api_integration_description'))
                                    ->extraAttributes([
                                        'class' => 'text-sm p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800'
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('resources.image'))
                    ->circular()
                    ->defaultImageUrl(url('/images/car-placeholder.jpg')),

                TextColumn::make('make')
                    ->label(__('resources.make'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('model')
                    ->label(__('resources.model'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label(__('resources.year'))
                    ->sortable(),

                TextColumn::make('plate_number')
                    ->label(__('resources.license'))
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable(),

                BadgeColumn::make('category')
                    ->label(__('resources.category'))
                    ->colors([
                        'success' => 'economy',
                        'info' => 'compact',
                        'warning' => 'midsize',
                        'primary' => 'fullsize',
                        'danger' => 'luxury',
                        'gray' => 'suv',
                    ]),

                BadgeColumn::make('status')
                    ->label(__('resources.status'))
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'maintenance',
                        'gray' => 'archived',
                    ]),

                BooleanColumn::make('is_available')
                    ->label(__('resources.available'))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('daily_rate')
                    ->label(__('resources.daily_rate'))
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label(__('resources.owner'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('category')
                    ->options([
                        'economy' => 'Economy',
                        'compact' => 'Compact',
                        'midsize' => 'Midsize',
                        'fullsize' => 'Full-size',
                        'luxury' => 'Luxury',
                        'suv' => 'SUV',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'published' => 'Published',
                        'draft' => 'Draft',
                        'maintenance' => 'Under Maintenance',
                        'archived' => 'Archived',
                    ]),

                SelectFilter::make('transmission')
                    ->options([
                        'automatic' => 'Automatic',
                        'manual' => 'Manual',
                        'cvt' => 'CVT',
                    ]),

                SelectFilter::make('is_available')
                    ->label('Availability')
                    ->options([
                        '1' => 'Available',
                        '0' => 'Not Available',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
            'view' => Pages\ViewVehicle::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'published')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::where('status', 'published')->count();
        return $count > 50 ? 'success' : ($count > 20 ? 'warning' : 'primary');
    }
}
