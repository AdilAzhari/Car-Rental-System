<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;

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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Review Overview')
                    ->icon('heroicon-m-star')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Review ID')
                                    ->prefix('RV-')
                                    ->weight('bold'),

                                TextEntry::make('rating')
                                    ->label('Overall Rating')
                                    ->formatStateUsing(fn ($state) => str_repeat('⭐', (int) $state) . ' (' . $state . '/5)')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('warning'),

                                TextEntry::make('recommendation')
                                    ->label('Recommends')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'yes' => 'success',
                                        'no' => 'danger',
                                        'maybe' => 'warning',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'yes' => 'Yes, would recommend',
                                        'no' => 'No, would not recommend',
                                        'maybe' => 'Maybe, with conditions',
                                        default => 'Not specified',
                                    }),
                            ]),
                    ]),

                Section::make('Customer & Booking Details')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('reviewer.name')
                                    ->label('Customer Name')
                                    ->weight('medium'),

                                TextEntry::make('reviewer.email')
                                    ->label('Customer Email')
                                    ->copyable(),

                                TextEntry::make('booking.id')
                                    ->label('Booking ID')
                                    ->prefix('BK-')
                                    ->url(fn ($record) => $record->booking ? route('filament.admin.resources.bookings.view', $record->booking) : null),

                                TextEntry::make('booking.vehicle')
                                    ->label('Vehicle')
                                    ->formatStateUsing(fn ($record) => $record->booking ? "{$record->booking->vehicle->make} {$record->booking->vehicle->model} ({$record->booking->vehicle->year})" : 'N/A'),
                            ]),
                    ]),

                Section::make('Review Content')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Review Title')
                            ->placeholder('No title provided')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpanFull(),

                        TextEntry::make('review_text')
                            ->label('Review Text')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Section::make('Detailed Ratings')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('vehicle_condition_rating')
                                    ->label('Vehicle Condition')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', (int) $state) . ' (' . $state . '/5)' : 'Not rated')
                                    ->placeholder('Not rated'),

                                TextEntry::make('cleanliness_rating')
                                    ->label('Cleanliness')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', (int) $state) . ' (' . $state . '/5)' : 'Not rated')
                                    ->placeholder('Not rated'),

                                TextEntry::make('service_rating')
                                    ->label('Customer Service')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', (int) $state) . ' (' . $state . '/5)' : 'Not rated')
                                    ->placeholder('Not rated'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Review Management')
                    ->icon('heroicon-m-shield-check')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Review Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger',
                                        'flagged' => 'info',
                                        default => 'gray',
                                    }),

                                TextEntry::make('visibility')
                                    ->label('Visibility')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'public' => 'success',
                                        'private' => 'warning',
                                        'hidden' => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('reviewed_at')
                                    ->label('Review Date')
                                    ->dateTime(),
                            ]),

                        TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->placeholder('No admin notes')
                            ->columnSpanFull()
                            ->visible(fn () => auth()->user()->role === 'admin'),
                    ]),

                Section::make('System Information')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Review Submitted')
                                    ->dateTime(),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Review Statistics')
                    ->icon('heroicon-m-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('helpful_votes')
                                    ->label('Helpful Votes')
                                    ->state(fn () => rand(0, 25)) // Placeholder - implement actual helpful votes
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('response_provided')
                                    ->label('Owner Response')
                                    ->state('Not yet') // Placeholder - implement owner responses
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('review_length')
                                    ->label('Review Length')
                                    ->state(fn ($record) => strlen($record->review_text) . ' characters')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}