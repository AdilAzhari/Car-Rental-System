<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Spatie\Activitylog\Models\Activity;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $label = 'Activity Log';

    protected static ?string $pluralLabel = 'Activity Logs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Activity Information')
                    ->description('System activity and audit trail details')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('log_name')
                                    ->label('Log Name')
                                    ->placeholder('default')
                                    ->maxLength(255),

                                TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Describe what happened'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('subject_type')
                                    ->label('Subject Type')
                                    ->options([
                                        'App\\Models\\User' => 'User',
                                        'App\\Models\\Vehicle' => 'Vehicle',
                                        'App\\Models\\Booking' => 'Booking',
                                        'App\\Models\\Review' => 'Review',
                                        'App\\Models\\Payment' => 'Payment',
                                    ])
                                    ->searchable()
                                    ->placeholder('Select model type'),

                                TextInput::make('subject_id')
                                    ->label('Subject ID')
                                    ->numeric()
                                    ->placeholder('Record ID'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('causer_type')
                                    ->label('Causer Type')
                                    ->options([
                                        'App\\Models\\User' => 'User',
                                    ])
                                    ->default('App\\Models\\User'),

                                TextInput::make('causer_id')
                                    ->label('Causer ID')
                                    ->numeric()
                                    ->placeholder('User ID who performed the action'),
                            ]),

                        DateTimePicker::make('created_at')
                            ->label('Timestamp')
                            ->default(now())
                            ->required(),
                    ]),

                Section::make('Activity Properties')
                    ->description('Detailed information about the activity')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        KeyValue::make('properties')
                            ->label('Properties')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->columnSpanFull(),

                        Textarea::make('event')
                            ->label('Event Type')
                            ->rows(2)
                            ->placeholder('created, updated, deleted, etc.')
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
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Activity')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                BadgeColumn::make('event')
                    ->label('Event')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'info' => 'viewed',
                        'primary' => fn ($state) => in_array($state, ['logged_in', 'logged_out']),
                        'gray' => 'default',
                    ]),

                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'System')
                    ->badge()
                    ->color('secondary')
                    ->sortable(),

                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),

                TextColumn::make('causer.role')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'owner',
                        'success' => 'renter',
                    ])
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),

                TextColumn::make('properties')
                    ->label('Properties')
                    ->formatStateUsing(fn ($state) => $state ? count($state) . ' items' : 'None')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log Type')
                    ->options(function () {
                        return Activity::distinct()
                            ->pluck('log_name', 'log_name')
                            ->filter()
                            ->sort();
                    }),

                SelectFilter::make('event')
                    ->label('Event Type')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'viewed' => 'Viewed',
                        'logged_in' => 'Logged In',
                        'logged_out' => 'Logged Out',
                    ]),

                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options([
                        'App\\Models\\User' => 'User',
                        'App\\Models\\Vehicle' => 'Vehicle',
                        'App\\Models\\Booking' => 'Booking',
                        'App\\Models\\Review' => 'Review',
                        'App\\Models\\Payment' => 'Payment',
                    ]),

                SelectFilter::make('causer_id')
                    ->label('User')
                    ->relationship('causer', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->form([
                        DateTimePicker::make('created_from')
                            ->label('From'),
                        DateTimePicker::make('created_until')
                            ->label('Until'),
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

                Filter::make('today')
                    ->label('Today\'s Activities')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),

                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Archive Selected')
                        ->modalHeading('Archive Activity Logs')
                        ->modalDescription('Are you sure you want to archive these activity logs? This action cannot be undone.'),
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
        
        if ($todayCount > 100) return 'danger';
        if ($todayCount > 50) return 'warning';
        if ($todayCount > 10) return 'info';
        
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