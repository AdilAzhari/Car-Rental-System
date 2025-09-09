<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Vehicle Added Successfully')
            ->body('The vehicle has been added to the fleet and is ready for configuration.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default owner to current user if not specified and user is owner
        if (!isset($data['owner_id']) && auth()->user()->role === 'owner') {
            $data['owner_id'] = auth()->id();
        }

        // Generate slug from make and model
        if (isset($data['make']) && isset($data['model'])) {
            $data['slug'] = \Str::slug($data['make'] . '-' . $data['model'] . '-' . $data['year']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create initial activity log entry
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->log('Vehicle added to fleet');
    }
}