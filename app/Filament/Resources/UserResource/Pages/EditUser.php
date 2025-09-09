<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            
            Action::make('verify')
                ->label('Verify User')
                ->icon('heroicon-m-check-badge')
                ->color(Color::Emerald)
                ->visible(fn () => !$this->record->is_verified)
                ->requiresConfirmation()
                ->modalHeading('Verify User Account')
                ->modalDescription('This will mark the user as verified and allow them to make bookings.')
                ->action(function () {
                    $this->record->update([
                        'is_verified' => true,
                        'email_verified_at' => now(),
                    ]);
                    
                    Notification::make()
                        ->success()
                        ->title('User Verified')
                        ->body('The user account has been verified successfully.')
                        ->send();
                }),

            Action::make('suspend')
                ->label('Suspend User')
                ->icon('heroicon-m-no-symbol')
                ->color(Color::Orange)
                ->visible(fn () => $this->record->is_active)
                ->requiresConfirmation()
                ->modalHeading('Suspend User Account')
                ->modalDescription('This will prevent the user from logging in.')
                ->action(function () {
                    $this->record->update(['is_active' => false]);
                    
                    Notification::make()
                        ->warning()
                        ->title('User Suspended')
                        ->body('The user account has been suspended.')
                        ->send();
                }),

            Action::make('activate')
                ->label('Activate User')
                ->icon('heroicon-m-check-circle')
                ->color(Color::Green)
                ->visible(fn () => !$this->record->is_active)
                ->action(function () {
                    $this->record->update(['is_active' => true]);
                    
                    Notification::make()
                        ->success()
                        ->title('User Activated')
                        ->body('The user account has been activated.')
                        ->send();
                }),
            
            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User Updated')
            ->body('The user details have been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}