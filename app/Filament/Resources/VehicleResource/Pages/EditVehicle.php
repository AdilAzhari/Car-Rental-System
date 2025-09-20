<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Check if user can edit this vehicle
        $user = auth()->user();
        if (! $user) {
            abort(403, 'You must be logged in to edit vehicles.');
        }

        if ($user->role === UserRole::ADMIN) {
            // Admin can edit all vehicles
            return;
        } elseif ($user->role === UserRole::OWNER) {
            // Owner can only edit their own vehicles
            if ($this->record->owner_id !== $user->id) {
                abort(403, 'You can only edit your own vehicles.');
            }
        } else {
            // Renters cannot edit vehicles
            abort(403, 'You do not have permission to edit vehicles.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('publish')
                ->label(__('resources.publish'))
                ->icon('heroicon-m-check-circle')
                ->color(Color::Emerald)
                ->visible(fn (): bool => $this->record->status !== VehicleStatus::PUBLISHED)
                ->requiresConfirmation()
                ->modalHeading('Publish Vehicle')
                ->modalDescription('This will make the vehicle visible and available for booking.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => VehicleStatus::PUBLISHED,
                        'is_available' => true,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('resources.vehicle_published'))
                        ->body(__('resources.vehicle_published_body'))
                        ->send();
                }),

            Action::make('maintenance')
                ->label(__('resources.mark_for_maintenance'))
                ->icon('heroicon-m-wrench-screwdriver')
                ->color(Color::Orange)
                ->visible(fn (): bool => $this->record->status !== VehicleStatus::MAINTENANCE)
                ->requiresConfirmation()
                ->modalHeading('Mark Vehicle for Maintenance')
                ->modalDescription('This will make the vehicle unavailable for booking.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => VehicleStatus::MAINTENANCE,
                        'is_available' => false,
                    ]);

                    Notification::make()
                        ->warning()
                        ->title(__('resources.vehicle_under_maintenance'))
                        ->body(__('resources.vehicle_under_maintenance_body'))
                        ->send();
                }),

            Action::make('archive')
                ->label(__('resources.archive'))
                ->icon('heroicon-m-archive-box')
                ->color(Color::Gray)
                ->visible(fn (): bool => $this->record->status !== VehicleStatus::ARCHIVED)
                ->requiresConfirmation()
                ->modalHeading('Archive Vehicle')
                ->modalDescription('This will remove the vehicle from active listings.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => VehicleStatus::ARCHIVED,
                        'is_available' => false,
                    ]);

                    Notification::make()
                        ->info()
                        ->title(__('resources.vehicle_archived'))
                        ->body(__('resources.vehicle_archived_body'))
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('resources.vehicle_updated'))
            ->body(__('resources.vehicle_updated_body'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // For owners, ensure they can't change the owner_id
        if (auth()->user() && auth()->user()->role === UserRole::OWNER) {
            $data['owner_id'] = auth()->id();
        }

        return $data;
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
