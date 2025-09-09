<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Booking Created Successfully')
            ->body('The booking has been created and is now pending confirmation.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        $data['payment_status'] = $data['payment_status'] ?? 'unpaid';
        $data['deposit_amount'] = $data['deposit_amount'] ?? 0;
        $data['commission_amount'] = $data['commission_amount'] ?? 0;

        return $data;
    }
}
