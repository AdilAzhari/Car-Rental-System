<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getNavigationGroup(): ?string
    {
        return __('resources.system_management');
    }

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        // Only admins can see activity logs
        $user = auth()->user();

        return $user && $user->role === 'admin';
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.activity_logs');
    }

    public static function getModelLabel(): string
    {
        return __('resources.activity_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.activity_logs');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('resources.activity_information'))
                    ->description(__('resources.activity_information_description'))
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('log_name')
                                    ->label(__('resources.log_name'))
                                    ->placeholder(__('resources.log_name_placeholder'))
                                    ->maxLength(255),

                                TextInput::make('description')
                                    ->label(__('resources.description'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('resources.activity_description_placeholder')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('subject_type')
                                    ->label(__('resources.subject_type'))
                                    ->options([
                                        \App\Models\User::class => __('resources.user'),
                                        \App\Models\Vehicle::class => __('resources.vehicle'),
                                        \App\Models\Booking::class => __('resources.booking'),
                                        \App\Models\Review::class => __('resources.review'),
                                        \App\Models\Payment::class => __('resources.payment'),
                                    ])
                                    ->searchable()
                                    ->placeholder(__('resources.select_model_type')),

                                TextInput::make('subject_id')
                                    ->label(__('resources.subject_id'))
                                    ->numeric()
                                    ->placeholder(__('resources.record_id_placeholder')),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('causer_type')
                                    ->label(__('resources.causer_type'))
                                    ->options([
                                        \App\Models\User::class => __('resources.user'),
                                    ])
                                    ->default(\App\Models\User::class),

                                TextInput::make('causer_id')
                                    ->label(__('resources.causer_id'))
                                    ->numeric()
                                    ->placeholder(__('resources.causer_id_placeholder')),
                            ]),

                        DateTimePicker::make('created_at')
                            ->label(__('resources.timestamp'))
                            ->default(now())
                            ->required(),
                    ]),

                Section::make(__('resources.activity_properties'))
                    ->description(__('resources.activity_properties_description'))
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        KeyValue::make('properties')
                            ->label(__('resources.properties'))
                            ->keyLabel(__('resources.attribute'))
                            ->valueLabel(__('resources.value'))
                            ->columnSpanFull(),

                        Textarea::make('event')
                            ->label(__('resources.event_type'))
                            ->rows(2)
                            ->placeholder(__('resources.event_type_placeholder'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.id'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('log_name')
                    ->label(__('resources.log_name'))
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label(__('resources.activity'))
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    }),

                BadgeColumn::make('event')
                    ->label(__('resources.event'))
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'info' => 'viewed',
                        'primary' => fn ($state): bool => in_array($state, ['logged_in', 'logged_out']),
                        'gray' => 'default',
                    ]),

                TextColumn::make('subject_type')
                    ->label(__('resources.subject'))
                    ->formatStateUsing(fn ($state): string|array|null => $state ? class_basename($state) : __('resources.system'))
                    ->badge()
                    ->color('secondary')
                    ->sortable(),

                TextColumn::make('subject_id')
                    ->label(__('resources.subject_id'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('causer.name')
                    ->label(__('resources.user'))
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('resources.system')),

                TextColumn::make('causer.role')
                    ->label(__('resources.role'))
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'owner',
                        'success' => 'renter',
                    ])
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('resources.timestamp'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),

                TextColumn::make('properties')
                    ->label(__('resources.properties'))
                    ->formatStateUsing(fn ($state) => $state ? count($state).' '.__('resources.items') : __('resources.none'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label(__('resources.log_type'))
                    ->options([
                        'default' => __('resources.log_name_placeholder'),
                        'user' => __('resources.user'),
                        'vehicle' => __('resources.vehicle'),
                        'booking' => __('resources.booking'),
                        'review' => __('resources.review'),
                        'payment' => __('resources.payment'),
                        'system' => __('resources.system'),
                    ]),

                SelectFilter::make('event')
                    ->label(__('resources.event_type'))
                    ->options([
                        'created' => __('resources.created'),
                        'updated' => __('resources.updated'),
                        'deleted' => __('resources.deleted'),
                        'viewed' => __('resources.viewed'),
                        'logged_in' => __('resources.logged_in'),
                        'logged_out' => __('resources.logged_out'),
                    ]),

                SelectFilter::make('subject_type')
                    ->label(__('resources.subject_type'))
                    ->options([
                        \App\Models\User::class => __('resources.user'),
                        \App\Models\Vehicle::class => __('resources.vehicle'),
                        \App\Models\Booking::class => __('resources.booking'),
                        \App\Models\Review::class => __('resources.review'),
                        \App\Models\Payment::class => __('resources.payment'),
                    ]),

                SelectFilter::make('causer_id')
                    ->label(__('resources.user'))
                    ->relationship('causer', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from')
                            ->label(__('resources.from')),
                        DateTimePicker::make('created_until')
                            ->label(__('resources.until')),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),

                Filter::make('today')
                    ->label(__('resources.todays_activities'))
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),

                Filter::make('this_week')
                    ->label(__('resources.this_week'))
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('resources.archive_selected'))
                        ->modalHeading(__('resources.archive_activity_logs'))
                        ->modalDescription(__('resources.archive_activity_logs_confirmation')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();

        return $todayCount > 0 ? (string) $todayCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();

        if ($todayCount > 100) {
            return 'danger';
        }
        if ($todayCount > 50) {
            return 'warning';
        }
        if ($todayCount > 10) {
            return 'info';
        }

        return 'primary';
    }

    public static function canCreate(): bool
    {
        return false; // Activity logs are created by the system, not manually
    }

    public static function canEdit($record): bool
    {
        return false; // Activity logs should not be editable
    }
}
