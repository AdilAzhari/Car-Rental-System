<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static UnitEnum|string|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();

        return $user && $user->role === UserRole::RENTER ? 'Browse & Book' : __('resources.vehicle_management');

    }

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        // All authenticated users can see vehicles (renters for browsing/booking, owners for management)
        return true;
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        return $user && $user->role === UserRole::RENTER ? __('resources.browse_vehicles') : __('resources.vehicles');
    }

    public static function getModelLabel(): string
    {
        return __('resources.vehicle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.vehicles');
    }

    #[\Override]
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

                        Grid::make()
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
                        Grid::make()
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
                                    ->placeholder(__('resources.select_or_create_owner'))
                                    ->default(fn () => auth()->user() && auth()->user()->role === UserRole::OWNER ? auth()->id() : null)
                                    ->hidden(fn (): bool => auth()->user() && auth()->user()->role === UserRole::OWNER),

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
                                        VehicleStatus::DRAFT->value => __('resources.draft'),
                                        VehicleStatus::PUBLISHED->value => __('resources.published'),
                                        VehicleStatus::MAINTENANCE->value => __('resources.under_maintenance'),
                                        VehicleStatus::ARCHIVED->value => __('resources.archived'),
                                    ])
                                    ->default(VehicleStatus::DRAFT->value)
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
                        Grid::make()
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

                        Grid::make()
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
                            ->visible(fn ($record): bool => $record && $record->traffic_violations && count($record->traffic_violations) > 0)
                            ->columnSpanFull(),

                        // API Integration Actions
                        Grid::make(1)
                            ->schema([
                                Placeholder::make('api_integration_info')
                                    ->label(__('resources.api_integration'))
                                    ->content(fn (): string => __('resources.api_integration_description'))
                                    ->extraAttributes([
                                        'class' => 'text-sm p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800',
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label(__('resources.image'))
                    ->size(80)
                    ->square()
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
                    ->getStateUsing(fn ($record) => $record->status instanceof VehicleStatus ? $record->status->value : (string) $record->status)
                    ->formatStateUsing(fn ($state): string => (string) $state)
                    ->colors([
                        'success' => VehicleStatus::PUBLISHED->value,
                        'warning' => VehicleStatus::DRAFT->value,
                        'danger' => VehicleStatus::MAINTENANCE->value,
                        'gray' => VehicleStatus::ARCHIVED->value,
                    ]),

                BooleanColumn::make('is_available')
                    ->label(__('resources.available'))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('daily_rate')
                    ->label(__('resources.daily_rate'))
                    ->money(config('app.currency', 'MYR'))
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

                BadgeColumn::make('has_pending_violations')
                    ->label(__('resources.traffic_violations'))
                    ->getStateUsing(function ($record): string {
                        if (empty($record->traffic_violations)) {
                            return __('vehicles.none');
                        }

                        $pendingCount = collect($record->traffic_violations)->where('status', 'pending')->count();

                        if ($pendingCount > 0) {
                            return $pendingCount.' pending';
                        }

                        return 'resolved';
                    })
                    ->colors([
                        'success' => 'none',
                        'warning' => 'resolved',
                        'danger' => fn ($state): bool => str_contains((string) $state, 'pending'),
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'none',
                        'heroicon-o-exclamation-triangle' => fn ($state): bool => str_contains((string) $state, 'pending'),
                        'heroicon-o-shield-check' => 'resolved',
                    ]),

                TextColumn::make('created_at')
                    ->label(__('resources.added'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'economy' => __('resources.economy'),
                        'compact' => __('resources.compact'),
                        'midsize' => __('resources.midsize'),
                        'fullsize' => __('enums.vehicle_category.fullsize'),
                        'luxury' => __('resources.luxury'),
                        'suv' => __('resources.suv'),
                    ]),

                SelectFilter::make('status')
                    ->options([
                        VehicleStatus::PUBLISHED->value => __('resources.published'),
                        VehicleStatus::DRAFT->value => __('resources.draft'),
                        VehicleStatus::MAINTENANCE->value => __('resources.under_maintenance'),
                        VehicleStatus::ARCHIVED->value => __('resources.archived'),
                    ]),

                SelectFilter::make('transmission')
                    ->options([
                        'automatic' => __('resources.automatic'),
                        'manual' => __('resources.manual'),
                        'cvt' => __('resources.cvt'),
                    ]),

                SelectFilter::make('is_available')
                    ->label(__('resources.availability'))
                    ->options([
                        '1' => __('resources.available'),
                        '0' => __('resources.not_available'),
                    ]),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label(__('widgets.export'))
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-tray'),
            ])
            ->actions(array_filter([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('book_now')
                    ->label(__('resources.book_now'))
                    ->icon('heroicon-m-calendar-plus')
                    ->color('success')
                    ->url(fn (Vehicle $vehicle): string => route('filament.admin.resources.bookings.create', [
                        'vehicle_id' => $vehicle->id,
                    ]))
                    ->visible(fn (Vehicle $vehicle): bool => auth()->user() &&
                        auth()->user()->role === UserRole::RENTER &&
                        $vehicle->status === VehicleStatus::PUBLISHED &&
                        $vehicle->is_available
                    ),
            ]))
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    FilamentExportBulkAction::make('bulk_export')
                        ->label(__('widgets.export'))
                        ->icon('heroicon-m-arrow-down-tray'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[\Override]
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
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->role === UserRole::ADMIN) {
            return static::getModel()::where('status', VehicleStatus::PUBLISHED->value)->count();
        } elseif ($user->role === UserRole::OWNER) {
            return static::getModel()::where('owner_id', $user->id)->count();
        } else {
            return static::getModel()::where('status', VehicleStatus::PUBLISHED->value)->where('is_available', true)->count();
        }
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = (int) static::getNavigationBadge();

        return $count > 10 ? 'success' : ($count > 5 ? 'warning' : 'primary');
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user && $user->role === UserRole::OWNER, fn ($query) => $query->where('owner_id', $user->id))
            ->when($user && $user->role === UserRole::RENTER, fn ($query) =>
                // Renters can only see published and available vehicles
                $query->where('status', VehicleStatus::PUBLISHED->value)->where('is_available', true))
            ->when(! $user, fn ($query) =>
                // If no authenticated user, return empty results
                $query->whereRaw('1 = 0'));
    }
}
