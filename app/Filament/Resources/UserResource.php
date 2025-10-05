<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\Schemas\UserInfolist;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\FilamentQueryOptimizationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string $policy = UserPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function getNavigationGroup(): ?string
    {
        return __('resources.user_management');
    }

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        // Only admins can see users in navigation
        $user = auth()->user();

        return $user && $user->role === UserRole::ADMIN;
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.users');
    }

    public static function getModelLabel(): string
    {
        return __('resources.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.users');
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('users.sections.personal_information'))
                    ->description(__('resources.user_details_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('users.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('resources.enter_full_name')),

                                TextInput::make('email')
                                    ->label(__('users.fields.email'))
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder(__('resources.email_placeholder')),

                                TextInput::make('phone')
                                    ->label(__('users.fields.phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder(__('resources.phone_placeholder')),

                                DateTimePicker::make('date_of_birth')
                                    ->label(__('users.fields.date_of_birth'))
                                    ->maxDate(now()->subYears(18))
                                    ->displayFormat('Y-m-d')
                                    ->helperText(__('resources.age_requirement')),
                            ]),
                    ]),

                Section::make(__('resources.account_settings'))
                    ->description(__('resources.account_settings_description'))
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('role')
                                    ->label(__('resources.user_role'))
                                    ->options([
                                        'admin' => __('enums.user_role.admin'),
                                        'owner' => __('enums.user_role.owner'),
                                        'renter' => __('enums.user_role.customer'),
                                    ])
                                    ->default('renter')
                                    ->required()
                                    ->native(false),

                                Toggle::make('is_verified')
                                    ->label(__('resources.account_verified'))
                                    ->helperText(__('resources.verified_users_helper'))
                                    ->default(false),

                                //                                Toggle::make('is_active')
                                //                                    ->label(__('resources.account_active'))
                                //                                    ->helperText(__('resources.inactive_accounts_helper'))
                                //                                    ->default(true),
                            ]),
                    ]),

                Section::make(__('resources.address_information'))
                    ->description(__('resources.address_information_description'))
                    ->icon('heroicon-m-map-pin')
                    ->collapsible()
                    ->schema([
                        Textarea::make('address')
                            ->label(__('resources.address'))
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder(__('resources.enter_full_address')),
                    ]),

                Section::make(__('resources.additional_information'))
                    ->description(__('resources.license_preferences_description'))
                    ->icon('heroicon-m-document-text')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('driver_license_number')
                                    ->label(__('resources.driver_license_number'))
                                    ->maxLength(50)
                                    ->helperText(__('resources.license_required_helper')),

                                DateTimePicker::make('license_expiry_date')
                                    ->label(__('resources.license_expiry_date'))
                                    ->minDate(now())
                                    ->displayFormat('Y-m-d'),

                                Select::make('preferred_language')
                                    ->label(__('resources.preferred_language'))
                                    ->options([
                                        'en' => __('resources.english'),
                                        'ms' => __('resources.bahasa_malaysia'),
                                    ])
                                    ->default('en')
                                    ->native(false),

                                Select::make('notification_preferences')
                                    ->label(__('resources.notifications'))
                                    ->options([
                                        'all' => __('resources.all_notifications'),
                                        'important' => __('resources.important_only'),
                                        'none' => __('resources.no_notifications'),
                                    ])
                                    ->default('all')
                                    ->native(false),
                            ]),

                        Textarea::make('notes')
                            ->label(__('resources.admin_notes'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->placeholder(__('resources.admin_notes_placeholder')),
                    ]),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('resources.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label(__('resources.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('role')
                    ->label(__('resources.role'))
                    ->getStateUsing(fn ($record) => $record->role instanceof UserRole ? $record->role->value : (string) $record->role)
                    ->formatStateUsing(fn ($state): string => (string) $state)
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'admin' => 'danger',
                        'owner' => 'warning',
                        'renter' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state): string => match ($state) {
                        'admin' => 'heroicon-m-shield-check',
                        'owner' => 'heroicon-m-building-storefront',
                        'renter' => 'heroicon-m-user',
                        default => 'heroicon-m-user',
                    }),

                BooleanColumn::make('is_verified')
                    ->label(__('resources.verified'))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),

                //                BooleanColumn::make('is_active')
                //                    ->label(__('resources.active'))
                //                    ->trueIcon('heroicon-o-check-circle')
                //                    ->falseIcon('heroicon-o-x-circle')
                //                    ->trueColor('success')
                //                    ->falseColor('gray'),

                TextColumn::make('phone')
                    ->label(__('resources.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('address')
                    ->label(__('resources.address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),

                TextColumn::make('bookings_count')
                    ->label(__('resources.bookings'))
                    ->counts('bookings')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label(__('resources.joined'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('resources.role'))
                    ->options([
                        'admin' => __('enums.user_role.admin'),
                        'owner' => __('enums.user_role.owner'),
                        'renter' => __('enums.user_role.customer'),
                    ]),

                SelectFilter::make('is_verified')
                    ->label(__('resources.verification_status'))
                    ->options([
                        '1' => __('resources.verified'),
                        '0' => __('resources.unverified'),
                    ]),

                //                SelectFilter::make('is_active')
                //                    ->label(__('resources.account_status'))
                //                    ->options([
                //                        '1' => __('resources.active'),
                //                        '0' => __('resources.inactive'),
                //                    ]),

                Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from')
                            ->label(__('resources.joined_from')),
                        DateTimePicker::make('created_until')
                            ->label(__('resources.joined_until')),
                    ])
                    ->query(fn (Builder $builder, array $data): Builder => $builder
                        ->when(
                            $data['created_from'],
                            fn (Builder $builder, $date): Builder => $builder->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $builder, $date): Builder => $builder->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label(__('widgets.export'))
                    ->color('success')
                    ->icon('heroicon-m-arrow-down-tray'),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn ($record): string => __('resources.user').': '.$record->name)
                    ->infolist(fn (): array => UserInfolist::configure(new \Filament\Schemas\Schema)->getComponents()),
                EditAction::make(),
                DeleteAction::make(),
            ])
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
            RelationManagers\VehiclesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        return 5;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            __('resources.email') => $record->email,
            __('resources.phone') => $record->phone ?: '—',
            __('resources.role') => ucfirst($record->role->value ?? $record->role),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $filamentQueryOptimizationService = app(FilamentQueryOptimizationService::class);

        $query = $filamentQueryOptimizationService->getOptimizedUserQuery()
            ->when($user && $user->role !== UserRole::ADMIN, fn ($q) =>
                // Non-admin users can only see their own profile
                $q->where('id', $user->id))
            ->when(! $user, fn ($q) =>
                // If no authenticated user, return empty results
                $q->whereRaw('1 = 0'));

        // Apply performance monitoring
        return $filamentQueryOptimizationService->monitorQueryPerformance($query, 'UserResource');
    }
}
