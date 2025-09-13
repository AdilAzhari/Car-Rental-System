<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If no renter_id is provided and user is admin, they can create bookings for others
        // If user is renter, set them as the renter
        if (!isset($data['renter_id']) && auth()->user()->role === UserRole::RENTER) {
            $data['renter_id'] = auth()->id();
        }

        return $data;
    }
}
