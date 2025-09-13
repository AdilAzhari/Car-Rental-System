<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Review Overview')
                    ->icon('heroicon-m-star')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('id')
                                    ->label(__('resources.review_id'))
                                    ->formatStateUsing(fn ($state): string => 'RV-'.$state)
                                    ->disabled(),

                                TextInput::make('rating')
                                    ->label(__('resources.overall_rating'))
                                    ->formatStateUsing(fn ($state): string => str_repeat('⭐', (int) $state).' ('.$state.'/5)')
                                    ->disabled(),

                                TextInput::make('recommendation')
                                    ->label(__('resources.recommends'))
                                    ->formatStateUsing(fn ($state): string => match ($state) {
                                        'yes' => 'Yes, would recommend',
                                        'no' => 'No, would not recommend',
                                        'maybe' => 'Maybe, with conditions',
                                        default => 'Not specified',
                                    })
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Customer & Booking Details')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('reviewer.name')
                                    ->label(__('resources.customer_name'))
                                    ->disabled(),

                                TextInput::make('reviewer.email')
                                    ->label(__('resources.customer_email'))
                                    ->disabled(),

                                TextInput::make('booking.id')
                                    ->label(__('resources.booking_id'))
                                    ->formatStateUsing(fn ($state): string => 'BK-'.$state)
                                    ->disabled(),

                                TextInput::make('booking_vehicle')
                                    ->label(__('resources.vehicle'))
                                    ->formatStateUsing(fn ($state, $record): string => $record->booking ? "{$record->booking->vehicle->make} {$record->booking->vehicle->model} ({$record->booking->vehicle->year})" : 'N/A')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Review Content')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->schema([
                        TextInput::make('title')
                            ->label('Review Title')
                            ->placeholder('No title provided')
                            ->disabled()
                            ->columnSpanFull(),

                        Textarea::make('review_text')
                            ->label(__('resources.review_text'))
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Detailed Ratings')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('vehicle_condition_rating')
                                    ->label('Vehicle Condition')
                                    ->formatStateUsing(fn ($state): string => $state ? str_repeat('⭐', (int) $state).' ('.$state.'/5)' : 'Not rated')
                                    ->disabled(),

                                TextInput::make('cleanliness_rating')
                                    ->label('Cleanliness')
                                    ->formatStateUsing(fn ($state): string => $state ? str_repeat('⭐', (int) $state).' ('.$state.'/5)' : 'Not rated')
                                    ->disabled(),

                                TextInput::make('service_rating')
                                    ->label('Customer Service')
                                    ->formatStateUsing(fn ($state): string => $state ? str_repeat('⭐', (int) $state).' ('.$state.'/5)' : 'Not rated')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Review Management')
                    ->icon('heroicon-m-shield-check')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('status')
                                    ->label('Review Status')
                                    ->disabled(),

                                TextInput::make('visibility')
                                    ->label(__('resources.visibility'))
                                    ->disabled(),

                                TextInput::make('reviewed_at')
                                    ->label('Review Date')
                                    ->disabled(),
                            ]),

                        Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->placeholder('No admin notes')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (): bool => auth()->user()->role === 'admin'),
                    ]),

                Section::make('System Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('created_at')
                                    ->label('Review Submitted')
                                    ->disabled(),

                                TextInput::make('updated_at')
                                    ->label(__('resources.last_updated'))
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Review Statistics')
                    ->icon('heroicon-m-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('helpful_votes')
                                    ->label('Helpful Votes')
                                    ->default('0')
                                    ->disabled(),

                                TextInput::make('response_provided')
                                    ->label('Owner Response')
                                    ->default('Not yet')
                                    ->disabled(),

                                TextInput::make('review_length')
                                    ->label('Review Length')
                                    ->formatStateUsing(fn ($state, $record): string => strlen((string) $record->review_text).' characters')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
