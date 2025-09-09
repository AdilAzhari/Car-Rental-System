<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->description('Basic user details and account information')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter full name'),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('user@example.com'),

                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('+1234567890'),

                                DateTimePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->maxDate(now()->subYears(18))
                                    ->displayFormat('Y-m-d')
                                    ->helperText('Must be at least 18 years old'),
                            ]),
                    ]),

                Section::make('Account Settings')
                    ->description('User role and account status')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('role')
                                    ->label('User Role')
                                    ->options([
                                        'admin' => 'Administrator',
                                        'owner' => 'Vehicle Owner',
                                        'renter' => 'Customer/Renter',
                                    ])
                                    ->default('renter')
                                    ->required()
                                    ->native(false),

                                Toggle::make('is_verified')
                                    ->label('Account Verified')
                                    ->helperText('Verified users can make bookings')
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Account Active')
                                    ->helperText('Inactive accounts cannot log in')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Address Information')
                    ->description('User location and contact details')
                    ->icon('heroicon-m-map-pin')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address')
                                    ->label('Street Address')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(100),

                                TextInput::make('state')
                                    ->label('State/Province')
                                    ->maxLength(100),

                                TextInput::make('postal_code')
                                    ->label('Postal Code')
                                    ->maxLength(20),

                                TextInput::make('country')
                                    ->label('Country')
                                    ->maxLength(100)
                                    ->default('Malaysia'),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->description('License and preferences')
                    ->icon('heroicon-m-document-text')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('driver_license_number')
                                    ->label('Driver License Number')
                                    ->maxLength(50)
                                    ->helperText('Required for vehicle rentals'),

                                DateTimePicker::make('license_expiry_date')
                                    ->label('License Expiry Date')
                                    ->minDate(now())
                                    ->displayFormat('Y-m-d'),

                                Select::make('preferred_language')
                                    ->label('Preferred Language')
                                    ->options([
                                        'en' => 'English',
                                        'ms' => 'Bahasa Malaysia',
                                    ])
                                    ->default('en')
                                    ->native(false),

                                Select::make('notification_preferences')
                                    ->label('Notifications')
                                    ->options([
                                        'all' => 'All Notifications',
                                        'important' => 'Important Only',
                                        'none' => 'No Notifications',
                                    ])
                                    ->default('all')
                                    ->native(false),
                            ]),

                        Textarea::make('notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->placeholder('Internal notes about this user...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'owner',
                        'success' => 'renter',
                    ])
                    ->icons([
                        'heroicon-m-shield-check' => 'admin',
                        'heroicon-m-building-storefront' => 'owner',
                        'heroicon-m-user' => 'renter',
                    ]),

                BooleanColumn::make('is_verified')
                    ->label('Verified')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),

                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('city')
                    ->label('City')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Administrator',
                        'owner' => 'Vehicle Owner',
                        'renter' => 'Customer/Renter',
                    ]),

                SelectFilter::make('is_verified')
                    ->label('Verification Status')
                    ->options([
                        '1' => 'Verified',
                        '0' => 'Unverified',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Account Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from')
                            ->label('Joined from'),
                        DateTimePicker::make('created_until')
                            ->label('Joined until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
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
            RelationManagers\VehiclesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 100 ? 'warning' : 'primary';
    }
}