<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BooleanEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Support\Colors\Color;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

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
                Section::make('Personal Information')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->icon('heroicon-m-phone')
                                    ->placeholder('Not provided'),

                                TextEntry::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->date()
                                    ->placeholder('Not provided'),
                            ]),
                    ]),

                Section::make('Account Details')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('role')
                                    ->label('User Role')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'admin' => 'danger',
                                        'owner' => 'warning',
                                        'renter' => 'success',
                                        default => 'gray',
                                    }),

                                BooleanEntry::make('is_verified')
                                    ->label('Account Verified')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                BooleanEntry::make('is_active')
                                    ->label('Account Active')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->dateTime()
                                    ->placeholder('Not verified'),

                                TextEntry::make('created_at')
                                    ->label('Account Created')
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make('Location Information')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('address')
                                    ->label('Street Address')
                                    ->placeholder('Not provided')
                                    ->columnSpanFull(),

                                TextEntry::make('city')
                                    ->label('City')
                                    ->placeholder('Not provided'),

                                TextEntry::make('state')
                                    ->label('State/Province')
                                    ->placeholder('Not provided'),

                                TextEntry::make('postal_code')
                                    ->label('Postal Code')
                                    ->placeholder('Not provided'),

                                TextEntry::make('country')
                                    ->label('Country')
                                    ->placeholder('Not provided'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Driver Information')
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('driver_license_number')
                                    ->label('License Number')
                                    ->placeholder('Not provided'),

                                TextEntry::make('license_expiry_date')
                                    ->label('License Expiry')
                                    ->date()
                                    ->placeholder('Not provided'),

                                TextEntry::make('preferred_language')
                                    ->label('Language')
                                    ->badge(),

                                TextEntry::make('notification_preferences')
                                    ->label('Notifications')
                                    ->badge(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Statistics')
                    ->icon('heroicon-m-chart-bar')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('bookings_count')
                                    ->label('Total Bookings')
                                    ->state(fn ($record) => $record->bookings->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('vehicles_count')
                                    ->label('Owned Vehicles')
                                    ->state(fn ($record) => $record->vehicles->count())
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('reviews_count')
                                    ->label('Reviews Given')
                                    ->state(fn ($record) => $record->reviews->count())
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Admin Notes')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('No notes available')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn () => auth()->user()->role === 'admin'),
            ]);
    }
}