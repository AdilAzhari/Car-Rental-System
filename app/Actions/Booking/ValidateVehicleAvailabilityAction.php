<?php

namespace App\Actions\Booking;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ValidateVehicleAvailabilityAction
{
    public function execute(int $vehicleId, string $startDate, string $endDate): Vehicle
    {
        $vehicle = Vehicle::findOrFail($vehicleId);

        // Check if vehicle is available for booking
        if (! $vehicle->is_available || $vehicle->status !== VehicleStatus::PUBLISHED) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'This vehicle is not available for booking.',
            ]);
        }

        // Check insurance expiry
        if ($vehicle->insurance_expiry && Carbon::parse($vehicle->insurance_expiry)->isPast()) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'This vehicle\'s insurance has expired and cannot be booked.',
            ]);
        }

        // Check for overlapping bookings
        $hasOverlappingBooking = $vehicle->bookings()
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($overlapQuery) use ($startDate, $endDate): void {
                        $overlapQuery->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($hasOverlappingBooking) {
            throw ValidationException::withMessages([
                'dates' => 'This vehicle is already booked for the selected dates.',
            ]);
        }

        return $vehicle;
    }
}
