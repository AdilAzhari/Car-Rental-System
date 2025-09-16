<?php

namespace App\Actions\Vehicle;

use App\Http\Resources\CarResource;
use App\Models\Vehicle;

class GetVehicleDetailsAction
{
    public function execute(int $vehicleId): CarResource
    {
        $vehicle = Vehicle::with([
            'owner',
            'images',
            'reviews.renter',
            'bookings' => function ($query): void {
                $query->where('status', '!=', 'cancelled')
                    ->where('end_date', '>=', now())
                    ->select(['vehicle_id', 'start_date', 'end_date', 'status']);
            },
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->findOrFail($vehicleId);

        return new CarResource($vehicle);
    }
}
