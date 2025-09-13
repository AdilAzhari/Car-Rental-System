<?php

use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Vehicle Management', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('Vehicle Listing', function (): void {
        it('allows admin to view all vehicles', function (): void {
            Vehicle::factory(5)->create();

            $this->actingAs($this->admin)
                ->get('/admin/vehicles')
                ->assertSuccessful()
                ->assertSee('Vehicles');
        });

        it('allows owner to view only their vehicles', function (): void {
            Vehicle::factory(3)->create(['owner_id' => $this->owner->id]);
            Vehicle::factory(2)->create(); // Other owners' vehicles

            $this->actingAs($this->owner)
                ->get('/admin/vehicles')
                ->assertSuccessful();
        });

        it('restricts renter access to vehicle management', function (): void {
            $this->actingAs($this->renter)
                ->get('/admin/vehicles')
                ->assertForbidden();
        });
    });

    describe('Vehicle Creation', function (): void {
        it('allows admin to create vehicles', function (): void {
            $vehicleData = [
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2023,
                'color' => 'White',
                'plate_number' => 'ABC-1234',
                'vin' => '1HGCM82633A123456',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'daily_rate' => 100.00,
                'description' => 'Test vehicle',
                'status' => VehicleStatus::PUBLISHED->value,
                'is_available' => true,
                'location' => 'Kuala Lumpur',
                'mileage' => 50000,
                'insurance_expiry' => now()->addMonths(12)->format('Y-m-d'),
                'owner_id' => $this->admin->id,
            ];

            $this->actingAs($this->admin)
                ->get('/admin/vehicles/create')
                ->assertSuccessful();
        });

        it('allows owner to create their own vehicles', function (): void {
            $this->actingAs($this->owner)
                ->get('/admin/vehicles/create')
                ->assertSuccessful();
        });

        it('automatically assigns owner_id when creating vehicle', function (): void {
            $vehicleData = [
                'make' => 'Honda',
                'model' => 'Civic',
                'year' => 2023,
                'color' => 'Black',
                'plate_number' => 'XYZ-5678',
                'vin' => '1HGCM82633A123457',
                'fuel_type' => 'petrol',
                'transmission' => 'manual',
                'seats' => 5,
                'daily_rate' => 85.00,
                'description' => 'Test civic',
                'status' => VehicleStatus::PUBLISHED->value,
                'is_available' => true,
                'location' => 'Kuala Lumpur',
                'mileage' => 30000,
                'insurance_expiry' => now()->addMonths(12)->format('Y-m-d'),
            ];

            $this->actingAs($this->owner)
                ->post('/admin/vehicles', $vehicleData);

            $vehicle = Vehicle::where('plate_number', 'XYZ-5678')->first();
            expect($vehicle->owner_id)->toBe($this->owner->id);
        });
    });

    describe('Vehicle Viewing', function (): void {
        beforeEach(function (): void {
            $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        });

        it('allows admin to view any vehicle', function (): void {
            $this->actingAs($this->admin)
                ->get("/admin/vehicles/{$this->vehicle->id}")
                ->assertSuccessful();
        });

        it('allows owner to view their own vehicles', function (): void {
            $this->actingAs($this->owner)
                ->get("/admin/vehicles/{$this->vehicle->id}")
                ->assertSuccessful();
        });

        it('prevents owner from viewing other owners vehicles', function (): void {
            $otherVehicle = Vehicle::factory()->create();

            $this->actingAs($this->owner)
                ->get("/admin/vehicles/{$otherVehicle->id}")
                ->assertForbidden();
        });
    });

    describe('Vehicle Updates', function (): void {
        beforeEach(function (): void {
            $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        });

        it('allows admin to update any vehicle', function (): void {
            $updateData = ['daily_rate' => 120.00];

            $this->actingAs($this->admin)
                ->patch("/admin/vehicles/{$this->vehicle->id}", $updateData);

            $this->vehicle->refresh();
            expect($this->vehicle->daily_rate)->toBe(120.00);
        });

        it('allows owner to update their own vehicles', function (): void {
            $updateData = ['daily_rate' => 95.00];

            $this->actingAs($this->owner)
                ->patch("/admin/vehicles/{$this->vehicle->id}", $updateData);

            $this->vehicle->refresh();
            expect($this->vehicle->daily_rate)->toBe(95.00);
        });

        it('prevents owner from updating other owners vehicles', function (): void {
            $otherVehicle = Vehicle::factory()->create();
            $updateData = ['daily_rate' => 200.00];

            $this->actingAs($this->owner)
                ->patch("/admin/vehicles/{$otherVehicle->id}", $updateData)
                ->assertForbidden();
        });
    });

    describe('Vehicle Deletion', function (): void {
        beforeEach(function (): void {
            $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        });

        it('allows admin to delete any vehicle', function (): void {
            $this->actingAs($this->admin)
                ->delete("/admin/vehicles/{$this->vehicle->id}");

            expect(Vehicle::find($this->vehicle->id))->toBeNull();
        });

        it('allows owner to delete their own vehicles', function (): void {
            $this->actingAs($this->owner)
                ->delete("/admin/vehicles/{$this->vehicle->id}");

            expect(Vehicle::find($this->vehicle->id))->toBeNull();
        });

        it('prevents owner from deleting other owners vehicles', function (): void {
            $otherVehicle = Vehicle::factory()->create();

            $this->actingAs($this->owner)
                ->delete("/admin/vehicles/{$otherVehicle->id}")
                ->assertForbidden();
        });
    });

    describe('Vehicle Status Management', function (): void {
        it('allows changing vehicle status', function (): void {
            $vehicle = Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'status' => VehicleStatus::PENDING,
            ]);

            $this->actingAs($this->admin)
                ->patch("/admin/vehicles/{$vehicle->id}", [
                    'status' => VehicleStatus::PUBLISHED->value,
                ]);

            $vehicle->refresh();
            expect($vehicle->status)->toBe(VehicleStatus::PUBLISHED);
        });
    });
});
