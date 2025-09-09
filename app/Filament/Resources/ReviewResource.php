<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static UnitEnum|string|null $navigationGroup = 'Customer Feedback';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Review Information')
                    ->description('Customer feedback and rating details')
                    ->icon('heroicon-m-star')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('booking_id')
                                    ->label('Booking')
                                    ->relationship('booking', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "Booking #{$record->id} - {$record->vehicle->make} {$record->vehicle->model}")
                                    ->createOptionForm([
                                        // Booking creation form would go here
                                    ]),

                                Select::make('reviewer_id')
                                    ->label('Reviewer')
                                    ->relationship('reviewer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select the customer who wrote this review'),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('rating')
                                    ->label('Rating')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->step(1)
                                    ->placeholder('1-5 stars')
                                    ->suffixIcon('heroicon-m-star')
                                    ->helperText('Rating from 1 (poor) to 5 (excellent) stars'),
                            ]),
                    ]),

                Section::make('Review Content')
                    ->description('Detailed feedback from the customer')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->schema([
                        Textarea::make('review_text')
                            ->label('Review Text')
                            ->required()
                            ->rows(6)
                            ->maxLength(1000)
                            ->placeholder('What did the customer think about their rental experience?')
                            ->helperText('Maximum 1000 characters')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Review Title')
                                    ->maxLength(200)
                                    ->placeholder('Brief summary of the review')
                                    ->helperText('Optional title for the review'),

                                Select::make('recommendation')
                                    ->label('Would Recommend?')
                                    ->options([
                                        'yes' => 'Yes, would recommend',
                                        'no' => 'No, would not recommend',
                                        'maybe' => 'Maybe, with conditions',
                                    ])
                                    ->native(false)
                                    ->placeholder('Select recommendation status'),
                            ]),
                    ]),

                Section::make('Review Categories')
                    ->description('Specific aspects rated by the customer')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('vehicle_condition_rating')
                                    ->label('Vehicle Condition')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->step(1)
                                    ->placeholder('1-5')
                                    ->suffixIcon('heroicon-m-wrench-screwdriver'),

                                TextInput::make('cleanliness_rating')
                                    ->label('Cleanliness')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->step(1)
                                    ->placeholder('1-5')
                                    ->suffixIcon('heroicon-m-sparkles'),

                                TextInput::make('service_rating')
                                    ->label('Customer Service')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->step(1)
                                    ->placeholder('1-5')
                                    ->suffixIcon('heroicon-m-user-group'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Review Status & Moderation')
                    ->description('Admin controls for review management')
                    ->icon('heroicon-m-shield-check')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('status')
                                    ->label('Review Status')
                                    ->options([
                                        'pending' => 'Pending Review',
                                        'approved' => 'Approved & Published',
                                        'rejected' => 'Rejected',
                                        'flagged' => 'Flagged for Review',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false),

                                Select::make('visibility')
                                    ->label('Visibility')
                                    ->options([
                                        'public' => 'Public (visible to all)',
                                        'private' => 'Private (admin only)',
                                        'hidden' => 'Hidden',
                                    ])
                                    ->default('public')
                                    ->native(false),

                                DateTimePicker::make('reviewed_at')
                                    ->label('Review Date')
                                    ->default(now())
                                    ->maxDate(now())
                                    ->displayFormat('Y-m-d H:i'),
                            ]),

                        Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Internal notes about this review...')
                            ->columnSpanFull()
                            ->helperText('Private notes visible only to administrators'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Review #')
                    ->sortable()
                    ->searchable()
                    ->prefix('RV-'),

                TextColumn::make('reviewer.name')
                    ->label('Reviewer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('booking.vehicle')
                    ->label('Vehicle')
                    ->formatStateUsing(fn ($record) => $record->booking ? "{$record->booking->vehicle->make} {$record->booking->vehicle->model}" : 'N/A')
                    ->searchable(),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', (int) $state) . ' (' . $state . '/5)')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'flagged',
                    ]),

                BadgeColumn::make('visibility')
                    ->label('Visibility')
                    ->colors([
                        'success' => 'public',
                        'warning' => 'private',
                        'gray' => 'hidden',
                    ]),

                TextColumn::make('recommendation')
                    ->label('Recommends')
                    ->badge()
                    ->colors([
                        'success' => 'yes',
                        'danger' => 'no',
                        'warning' => 'maybe',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'yes' => 'Yes',
                        'no' => 'No',
                        'maybe' => 'Maybe',
                        default => 'N/A',
                    }),

                TextColumn::make('title')
                    ->label('Title')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('review_text')
                    ->label('Review')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                TextColumn::make('reviewed_at')
                    ->label('Review Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->options([
                        '5' => '5 Stars',
                        '4' => '4 Stars',
                        '3' => '3 Stars',
                        '2' => '2 Stars',
                        '1' => '1 Star',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'flagged' => 'Flagged',
                    ]),

                SelectFilter::make('recommendation')
                    ->options([
                        'yes' => 'Recommends',
                        'no' => 'Does Not Recommend',
                        'maybe' => 'Maybe Recommends',
                    ]),

                Filter::make('high_rating')
                    ->label('High Ratings (4+ stars)')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '>=', 4)),

                Filter::make('low_rating')
                    ->label('Low Ratings (2 or less)')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '<=', 2)),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? 'warning' : 'primary';
    }
}