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

    #[\Override]
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

                Section::make(__('resources.account_details'))
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('role')
                                    ->label(__('resources.user_role'))
                                    ->badge(),

                                IconEntry::make('is_verified')
                                    ->label(__('resources.account_verified'))
                                    ->boolean(),

                                IconEntry::make('status')
                                    ->label(__('resources.status'))
                                    ->boolean(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email_verified_at')
                                    ->label(__('resources.email_verified_at'))
                                    ->dateTime(),

                                TextEntry::make('created_at')
                                    ->label(__('resources.account_created'))
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make(__('resources.location_information'))
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('address')
                                    ->label(__('resources.street_address'))
                                    ->columnSpanFull(),

                                TextEntry::make('city')
                                    ->label(__('resources.city')),

                                TextEntry::make('state')
                                    ->label(__('resources.state_province')),

                                TextEntry::make('postal_code')
                                    ->label(__('resources.postal_code')),

                                TextEntry::make('country')
                                    ->label(__('resources.country')),
                            ]),
                    ])
                    ->collapsible(),

                Section::make(__('resources.driver_information'))
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('driver_license_number')
                                    ->label(__('resources.license_number')),

                                TextEntry::make('license_expiry_date')
                                    ->label(__('resources.license_expiry'))
                                    ->date(),

                                TextEntry::make('preferred_language')
                                    ->label(__('resources.language')),

                                TextEntry::make('notification_preferences')
                                    ->label(__('resources.notifications')),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
