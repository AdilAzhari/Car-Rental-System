<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Schemas\UserInfolist;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->modalHeading(fn (): string => __('resources.user').': '.$this->record->name)
                ->infolist(fn (): array => UserInfolist::configure(new \Filament\Schemas\Schema)->getComponents()),

            Action::make('verify')
                ->label(__('resources.verify_user'))
                ->icon('heroicon-m-check-badge')
                ->color(Color::Emerald)
                ->visible(fn (): bool => ! $this->record->is_verified)
                ->requiresConfirmation()
                ->modalHeading(__('resources.verify_user_account'))
                ->modalDescription(__('resources.verify_user_description'))
                ->action(function (): void {
                    $this->record->update([
                        'is_verified' => true,
                        'email_verified_at' => now(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('resources.user_verified'))
                        ->body(__('resources.user_verified_body'))
                        ->send();
                }),

            //            Action::make('suspend')
            //                ->label(__('resources.suspend_user'))
            //                ->icon('heroicon-m-no-symbol')
            //                ->color(Color::Orange)
            // //                ->visible(fn () => $this->record->is_active)
            //                ->requiresConfirmation()
            //                ->modalHeading(__('resources.suspend_user_account'))
            //                ->modalDescription(__('resources.suspend_user_description'))
            //                ->action(function (): void {
            // //                    $this->record->update(['is_active' => false]);
            //
            //                    Notification::make()
            //                        ->warning()
            //                        ->title(__('resources.user_suspended'))
            //                        ->body(__('resources.user_suspended_body'))
            //                        ->send();
            //                }),

            //            Action::make('activate')
            //                ->label(__('resources.activate_user'))
            //                ->icon('heroicon-m-check-circle')
            //                ->color(Color::Green)
            // //                ->visible(fn (): bool => ! $this->record->is_active)
            //                ->action(function (): void {
            // //                    $this->record->update(['is_active' => true]);
            //
            //                    Notification::make()
            //                        ->success()
            //                        ->title(__('resources.user_activated'))
            //                        ->body(__('resources.user_activated_body'))
            //                        ->send();
            //                }),

            DeleteAction::make(),
        ];
    }

    #[\Override]
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('resources.user_updated'))
            ->body(__('resources.user_updated_body'));
    }

    #[\Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
