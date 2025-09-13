<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_profile')
                ->label(__('resources.view_user_profile'))
                ->icon('heroicon-m-user-circle')
                ->color('info')
                ->url(fn (): ?string => $this->getRecord()->id === auth()->id() ? '/admin/profile' : null)
                ->visible(fn (): bool => $this->getRecord()->id === auth()->id())
                ->openUrlInNewTab(false),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('resources.personal_information'))
                    ->icon('heroicon-m-user')
                    ->description(fn (): string|array|null => $this->getRecord()->id === auth()->id() ?
                        __('resources.view_own_profile_description') :
                        __('resources.view_user_profile_description'))
                    ->headerActions([
                        Action::make('edit_profile')
                            ->label(__('resources.edit_profile'))
                            ->icon('heroicon-m-pencil-square')
                            ->color('primary')
                            ->size('sm')
                            ->url('/admin/profile')
                            ->visible(fn (): bool => $this->getRecord()->id === auth()->id()),
                    ])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('resources.name')),

                                TextEntry::make('email')
                                    ->label(__('resources.email'))
                                    ->copyable(),

                                TextEntry::make('phone')
                                    ->label(__('resources.phone')),

                                TextEntry::make('date_of_birth')
                                    ->label(__('resources.date_of_birth'))
                                    ->date(),
                            ]),
                    ]),

                Section::make('Account Details')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('role')
                                    ->label('User Role')
                                    ->badge(),

                                IconEntry::make('is_verified')
                                    ->label('Account Verified')
                                    ->boolean(),

                                IconEntry::make('is_active')
                                    ->label('Account Active')
                                    ->boolean(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->dateTime(),

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
                                    ->columnSpanFull(),

                                TextEntry::make('city')
                                    ->label('City'),

                                TextEntry::make('state')
                                    ->label('State/Province'),

                                TextEntry::make('postal_code')
                                    ->label('Postal Code'),

                                TextEntry::make('country')
                                    ->label('Country'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Driver Information')
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('driver_license_number')
                                    ->label('License Number'),

                                TextEntry::make('license_expiry_date')
                                    ->label('License Expiry')
                                    ->date(),

                                TextEntry::make('preferred_language')
                                    ->label('Language'),

                                TextEntry::make('notification_preferences')
                                    ->label('Notifications'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
