<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\VehicleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Str;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

    #[\Override]
    public function mount(): void
    {
        //             Check if user can create vehicles
        if (! auth()->user() || ! in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::OWNER])) {
            abort(403, 'You do not have permission to create vehicles.');
        }

        parent::mount();
    }

    #[\Override]
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('resources.vehicle_added_successfully'))
            ->body(__('resources.vehicle_added_body'));
    }

    #[\Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default owner to current user if not specified and user is owner
        if (! isset($data['owner_id']) && auth()->user()->role === UserRole::OWNER) {
            $data['owner_id'] = auth()->id();
        }

        // Generate slug from make and model
        if (isset($data['make']) && isset($data['model'])) {
            $data['slug'] = Str::slug($data['make'].'-'.$data['model'].'-'.$data['year']);
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
