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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-star';

    public static function getNavigationGroup(): ?string
    {
        return __('resources.customer_feedback');
    }

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('resources.reviews');
    }

    public static function getModelLabel(): string
    {
        return __('resources.review');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.reviews');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('resources.review_information'))
                    ->description(__('resources.customer_feedback'))
                    ->icon('heroicon-m-star')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('booking_id')
                                    ->label(__('resources.booking'))
                                    ->relationship('booking', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->getOptionLabelFromRecordUsing(fn ($record): string => "Booking #{$record->id} - {$record->vehicle->make} {$record->vehicle->model}")
                                    ->createOptionForm([
                                        // Booking creation form would go here
                                    ]),

                                Select::make('renter_id')
                                    ->label(__('resources.reviewer'))
                                    ->relationship('renter', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder(__('resources.select_reviewer_placeholder')),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('rating')
                                    ->label(__('resources.rating'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->step(1)
                                    ->placeholder(__('resources.rating_placeholder'))
                                    ->suffixIcon('heroicon-m-star')
                                    ->helperText(__('resources.rating_helper_text')),
                            ]),
                    ]),

                Section::make(__('resources.review_content'))
                    ->description(__('resources.review_content_description'))
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->schema([
                        Textarea::make('comment')
                            ->label(__('resources.comment'))
                            ->required()
                            ->rows(6)
                            ->maxLength(1000)
                            ->placeholder(__('resources.review_comment_placeholder'))
                            ->helperText(__('resources.max_characters', ['count' => 1000]))
                            ->columnSpanFull(),

                        Toggle::make('is_visible')
                            ->label(__('resources.visible_to_public'))
                            ->default(true)
                            ->helperText(__('resources.review_visibility_helper')),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('resources.review_id'))
                    ->sortable()
                    ->searchable()
                    ->prefix('RV-'),

                TextColumn::make('renter.name')
                    ->label(__('resources.reviewer'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('vehicle.make')
                    ->label(__('resources.vehicle'))
                    ->formatStateUsing(fn ($record): string|array|null => $record->vehicle ? "{$record->vehicle->make} {$record->vehicle->model}" : __('resources.na'))
                    ->searchable(['vehicles.make', 'vehicles.model']),

                TextColumn::make('rating')
                    ->label(__('resources.rating'))
                    ->formatStateUsing(fn ($state): string => str_repeat('⭐', (int) $state).' ('.$state.'/5)')
                    ->sortable(),

                TextColumn::make('is_visible')
                    ->label(__('resources.visibility'))
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string|array|null => $state ? __('resources.visible') : __('resources.hidden')),

                TextColumn::make('comment')
                    ->label(__('resources.comment'))
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    }),

                TextColumn::make('created_at')
                    ->label(__('resources.submitted'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label(__('resources.rating'))
                    ->options([
                        '5' => __('resources.5_stars'),
                        '4' => __('resources.4_stars'),
                        '3' => __('resources.3_stars'),
                        '2' => __('resources.2_stars'),
                        '1' => __('resources.1_star'),
                    ]),

                SelectFilter::make('is_visible')
                    ->label(__('resources.visibility'))
                    ->options([
                        1 => __('resources.visible'),
                        0 => __('resources.hidden'),
                    ]),

                Filter::make('high_rating')
                    ->label(__('resources.high_rating'))
                    ->query(fn (Builder $query): Builder => $query->where('rating', '>=', 4)),

                Filter::make('low_rating')
                    ->label(__('resources.low_rating'))
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(auth()->user()->role === 'renter', fn ($query) => $query->where('renter_id', auth()->id()))
            ->when(auth()->user()->role === 'owner', fn ($query) => $query->whereHas('vehicle', function ($vehicleQuery): void {
                $vehicleQuery->where('owner_id', auth()->id());
            }));
    }
}
