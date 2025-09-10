<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

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
                ->url(fn () => $this->getRecord()->id === auth()->id() ? '/admin/profile' : null)
                ->visible(fn () => $this->getRecord()->id === auth()->id())
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
                    ->description(fn () => $this->getRecord()->id === auth()->id() ? 
                        __('resources.view_own_profile_description') : 
                        __('resources.view_user_profile_description'))
                    ->headerActions([
                        Action::make('edit_profile')
                            ->label(__('resources.edit_profile'))
                            ->icon('heroicon-m-pencil-square')
                            ->color('primary')
                            ->size('sm')
                            ->url('/admin/profile')
                            ->visible(fn () => $this->getRecord()->id === auth()->id()),
                    ])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('resources.name'))
                                    ->disabled(),

                                TextInput::make('email')
                                    ->label(__('resources.email'))
                                    ->disabled(),

                                TextInput::make('phone')
                                    ->label(__('resources.phone'))
                                    ->disabled(),

                                TextInput::make('date_of_birth')
                                    ->label(__('resources.date_of_birth'))
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Account Details')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('role')
                                    ->label('User Role')
                                    ->disabled(),

                                Toggle::make('is_verified')
                                    ->label('Account Verified')
                                    ->disabled(),

                                Toggle::make('is_active')
                                    ->label('Account Active')
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->disabled(),

                                TextInput::make('created_at')
                                    ->label('Account Created')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Location Information')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('address')
                                    ->label('Street Address')
                                    ->disabled()
                                    ->columnSpanFull(),

                                TextInput::make('city')
                                    ->label('City')
                                    ->disabled(),

                                TextInput::make('state')
                                    ->label('State/Province')
                                    ->disabled(),

                                TextInput::make('postal_code')
                                    ->label('Postal Code')
                                    ->disabled(),

                                TextInput::make('country')
                                    ->label('Country')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Driver Information')
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('driver_license_number')
                                    ->label('License Number')
                                    ->disabled(),

                                TextInput::make('license_expiry_date')
                                    ->label('License Expiry')
                                    ->disabled(),

                                TextInput::make('preferred_language')
                                    ->label('Language')
                                    ->disabled(),

                                TextInput::make('notification_preferences')
                                    ->label('Notifications')
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}
