<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-m-check-circle')
                ->color(Color::Emerald)
                ->visible(fn () => $this->record->status !== 'published')
                ->requiresConfirmation()
                ->modalHeading('Publish Vehicle')
                ->modalDescription('This will make the vehicle visible and available for booking.')
                ->action(function () {
                    $this->record->update([
                        'status' => 'published',
                        'is_available' => true,
                    ]);
                    
                    Notification::make()
                        ->success()
                        ->title('Vehicle Published')
                        ->body('The vehicle is now live and available for booking.')
                        ->send();
                }),

            Action::make('maintenance')
                ->label('Mark for Maintenance')
                ->icon('heroicon-m-wrench-screwdriver')
                ->color(Color::Orange)
                ->visible(fn () => $this->record->status !== 'maintenance')
                ->requiresConfirmation()
                ->modalHeading('Mark Vehicle for Maintenance')
                ->modalDescription('This will make the vehicle unavailable for booking.')
                ->action(function () {
                    $this->record->update([
                        'status' => 'maintenance',
                        'is_available' => false,
                    ]);
                    
                    Notification::make()
                        ->warning()
                        ->title('Vehicle Under Maintenance')
                        ->body('The vehicle has been marked for maintenance.')
                        ->send();
                }),

            Action::make('archive')
                ->label('Archive')
                ->icon('heroicon-m-archive-box')
                ->color(Color::Gray)
                ->visible(fn () => $this->record->status !== 'archived')
                ->requiresConfirmation()
                ->modalHeading('Archive Vehicle')
                ->modalDescription('This will remove the vehicle from active listings.')
                ->action(function () {
                    $this->record->update([
                        'status' => 'archived',
                        'is_available' => false,
                    ]);
                    
                    Notification::make()
                        ->info()
                        ->title('Vehicle Archived')
                        ->body('The vehicle has been archived.')
                        ->send();
                }),
            
            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Vehicle Updated')
            ->body('The vehicle details have been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterSave(): void
    {
        // Log the update activity
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->log('Vehicle details updated');
    }
}