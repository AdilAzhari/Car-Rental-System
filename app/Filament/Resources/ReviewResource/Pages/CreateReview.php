<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Review Added Successfully')
            ->body('The review has been added and is pending approval.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set current user as renter if they are a renter role
        if (auth()->user()->role === 'renter') {
            $data['renter_id'] = auth()->id();
        }

        // Set default visibility to true if not specified
        if (! isset($data['is_visible'])) {
            $data['is_visible'] = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Create activity log entry
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->log('Review created and pending approval');

        // TODO: Send notification to administrators about new review
    }
}
