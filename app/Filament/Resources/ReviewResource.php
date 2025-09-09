<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use BackedEnum;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-star';

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

                                Select::make('renter_id')
                                    ->label('Reviewer')
                                    ->relationship('renter', 'name')
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
                        Textarea::make('comment')
                            ->label('Review Comment')
                            ->required()
                            ->rows(6)
                            ->maxLength(1000)
                            ->placeholder('What did the customer think about their rental experience?')
                            ->helperText('Maximum 1000 characters')
                            ->columnSpanFull(),

                        Toggle::make('is_visible')
                            ->label('Visible to Public')
                            ->default(true)
                            ->helperText('Whether this review should be visible to other users'),
                    ]),

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

                TextColumn::make('renter.name')
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

                BadgeColumn::make('is_visible')
                    ->label('Visibility')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Visible' : 'Hidden'),

                TextColumn::make('comment')
                    ->label('Review Comment')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

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

                SelectFilter::make('is_visible')
                    ->label('Visibility')
                    ->options([
                        1 => 'Visible',
                        0 => 'Hidden',
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
        return static::getModel()::count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $totalCount = static::getModel()::count();
        return $totalCount > 0 ? 'primary' : 'gray';
    }
}
