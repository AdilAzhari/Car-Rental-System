<?php

namespace App\Observers;

use App\Models\Vehicle;

class VehicleObserver
{
    /**
     * Handle the Vehicle "created" event.
     */
    public function created(Vehicle $vehicle): void
    {
        // Notify the vehicle owner about the new vehicle
        $vehicle->owner->notify(new \App\Notifications\VehicleCreated($vehicle));

        // Also notify the first user (assuming it's an admin) as a fallback
        $firstUser = \App\Models\User::first();
        if ($firstUser && $firstUser->id !== $vehicle->owner_id) {
            $firstUser->notify(new \App\Notifications\VehicleCreated($vehicle));
        }
    }

    /**
     * Handle the Vehicle "updated" event.
     */
    public function updated(Vehicle $vehicle): void
    {
        //
    }

    /**
     * Handle the Vehicle "deleted" event.
     */
    public function deleted(Vehicle $vehicle): void
    {
        //
    }

    /**
     * Handle the Vehicle "restored" event.
     */
    public function restored(Vehicle $vehicle): void
    {
        //
    }

    /**
     * Handle the Vehicle "force deleted" event.
     */
    public function forceDeleted(Vehicle $vehicle): void
    {
        //
    }
}
