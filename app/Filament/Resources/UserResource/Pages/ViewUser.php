<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->disabled(),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->disabled(),

                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->disabled(),

                                TextInput::make('date_of_birth')
                                    ->label('Date of Birth')
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