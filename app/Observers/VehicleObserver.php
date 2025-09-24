<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\VehicleCreated;

class VehicleObserver
{
    /**
     * Handle the Vehicle "created" event.
     */
    public function created(Vehicle $vehicle): void
    {
        // Notify the vehicle owner about the new vehicle
        $vehicle->owner->notify(new VehicleCreated($vehicle));

        // Also notify the first user (assuming it's an admin) as a fallback
        $firstUser = User::query()->first();
        if ($firstUser && $firstUser->id !== $vehicle->owner_id) {
            $firstUser->notify(new VehicleCreated($vehicle));
        }
    }
}
