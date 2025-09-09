<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Vehicle Information')
                    ->description('Basic vehicle details and specifications')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('make')
                                    ->label('Make')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Toyota, Honda, BMW...'),

                                TextInput::make('model')
                                    ->label('Model')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Camry, Civic, X5...'),

                                TextInput::make('year')
                                    ->label('Year')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(now()->year + 1)
                                    ->placeholder('2023'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('license_plate')
                                    ->label('License Plate')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(20)
                                    ->placeholder('ABC-1234')
                                    ->suffixIcon('heroicon-m-identification'),

                                TextInput::make('vin')
                                    ->label('VIN Number')
                                    ->maxLength(17)
                                    ->placeholder('1HGBH41JXMN109186')
                                    ->helperText('Vehicle Identification Number'),
                            ]),
                    ]),

                Section::make('Vehicle Categories')
                    ->description('Classification and type information')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('category')
                                    ->label('Category')
                                    ->options([
                                        'economy' => 'Economy',
                                        'compact' => 'Compact',
                                        'midsize' => 'Midsize',
                                        'fullsize' => 'Full-size',
                                        'luxury' => 'Luxury',
                                        'suv' => 'SUV',
                                        'minivan' => 'Minivan',
                                        'pickup' => 'Pickup Truck',
                                        'convertible' => 'Convertible',
                                        'sports' => 'Sports Car',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('transmission')
                                    ->label('Transmission')
                                    ->options([
                                        'automatic' => 'Automatic',
                                        'manual' => 'Manual',
                                        'cvt' => 'CVT',
                                    ])
                                    ->required()
                                    ->native(false),

                                Select::make('fuel_type')
                                    ->label('Fuel Type')
                                    ->options([
                                        'petrol' => 'Petrol',
                                        'diesel' => 'Diesel',
                                        'hybrid' => 'Hybrid',
                                        'electric' => 'Electric',
                                        'lpg' => 'LPG',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('seating_capacity')
                                    ->label('Seats')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->default(5)
                                    ->suffixIcon('heroicon-m-user-group'),

                                TextInput::make('doors')
                                    ->label('Doors')
                                    ->numeric()
                                    ->minValue(2)
                                    ->maxValue(6)
                                    ->default(4),

                                TextInput::make('engine_size')
                                    ->label('Engine Size (L)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0.1)
                                    ->placeholder('2.0'),

                                TextInput::make('mileage')
                                    ->label('Mileage (km)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('50000'),
                            ]),
                    ]),

                Section::make('Ownership & Pricing')
                    ->description('Owner details and rental pricing')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('owner_id')
                                    ->label('Vehicle Owner')
                                    ->relationship('owner', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('email')->email()->required(),
                                        Select::make('role')->options(['owner' => 'Owner'])->default('owner'),
                                    ])
                                    ->placeholder('Select or create owner'),

                                TextInput::make('daily_rate')
                                    ->label('Daily Rate (MYR)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('RM')
                                    ->step(0.01)
                                    ->placeholder('150.00'),
                            ]),
                    ]),

                Section::make('Vehicle Status & Location')
                    ->description('Current status and location information')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'published' => 'Published',
                                        'maintenance' => 'Under Maintenance',
                                        'archived' => 'Archived',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),

                                Toggle::make('is_available')
                                    ->label('Available for Rent')
                                    ->default(true)
                                    ->helperText('Can customers book this vehicle?'),

                                Toggle::make('insurance_included')
                                    ->label('Insurance Included')
                                    ->default(true)
                                    ->helperText('Does rental include insurance?'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('location')
                                    ->label('Current Location')
                                    ->maxLength(255)
                                    ->placeholder('Kuala Lumpur, Malaysia'),

                                TextInput::make('pickup_location')
                                    ->label('Pickup Location')
                                    ->maxLength(255)
                                    ->placeholder('Airport, Hotel, etc.'),
                            ]),
                    ]),

                Section::make('Images & Media')
                    ->description('Vehicle photos and documentation')
                    ->icon('heroicon-m-photo')
                    ->schema([
                        FileUpload::make('featured_image')
                            ->label('Featured Image')
                            ->image()
                            ->directory('vehicles/featured')
                            ->maxSize(5120)
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600),

                        FileUpload::make('gallery_images')
                            ->label('Gallery Images')
                            ->multiple()
                            ->image()
                            ->directory('vehicles/gallery')
                            ->maxFiles(10)
                            ->maxSize(5120)
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->helperText('Add up to 10 additional photos'),

                        FileUpload::make('documents')
                            ->label('Documents')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->directory('vehicles/documents')
                            ->maxFiles(5)
                            ->helperText('Upload registration, insurance, etc.'),
                    ])
                    ->collapsible(),

                Section::make('Features & Specifications')
                    ->description('Vehicle features and additional specifications')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        KeyValue::make('features')
                            ->label('Vehicle Features')
                            ->keyLabel('Feature')
                            ->valueLabel('Details')
                            ->default([
                                'Air Conditioning' => 'Yes',
                                'Bluetooth' => 'Yes',
                                'GPS Navigation' => 'Yes',
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Describe the vehicle, its condition, special features, etc.')
                            ->columnSpanFull(),

                        Textarea::make('terms_and_conditions')
                            ->label('Terms & Conditions')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Any specific terms for this vehicle...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/car-placeholder.jpg')),

                TextColumn::make('make')
                    ->label('Make')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                TextColumn::make('license_plate')
                    ->label('License')
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable(),

                BadgeColumn::make('category')
                    ->label('Category')
                    ->colors([
                        'success' => 'economy',
                        'info' => 'compact',
                        'warning' => 'midsize',
                        'primary' => 'fullsize',
                        'danger' => 'luxury',
                        'gray' => 'suv',
                    ]),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                        'danger' => 'maintenance',
                        'gray' => 'archived',
                    ]),

                BooleanColumn::make('is_available')
                    ->label('Available')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->money('MYR')
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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