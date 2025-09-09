<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

describe('Vehicle Management Browser Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('Vehicle Listing and Search', function () {
        it('displays vehicle list correctly', function () {
            Vehicle::factory(5)->create([
                'owner_id' => $this->owner->id,
                'status' => 'published'
            ]);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->assertSee('Vehicle Management')
                    ->assertSee('Add New Vehicle')
                    ->assertPresent('[data-testid="vehicle-list"]')
                    ->assertElementCount('[data-testid="vehicle-card"]', 5);
            });
        });

        it('can search vehicles by make and model', function () {
            Vehicle::factory()->create([
                'make' => 'Toyota',
                'model' => 'Camry',
                'owner_id' => $this->owner->id
            ]);
            Vehicle::factory()->create([
                'make' => 'Honda',
                'model' => 'Civic',
                'owner_id' => $this->owner->id
            ]);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->type('[data-testid="search-input"]', 'Toyota')
                    ->pause(500)
                    ->assertSee('Toyota Camry')
                    ->assertDontSee('Honda Civic')
                    ->clear('[data-testid="search-input"]')
                    ->type('[data-testid="search-input"]', 'Civic')
                    ->pause(500)
                    ->assertSee('Honda Civic')
                    ->assertDontSee('Toyota Camry');
            });
        });

        it('can filter vehicles by status', function () {
            Vehicle::factory()->published()->create(['owner_id' => $this->owner->id]);
            Vehicle::factory()->draft()->create(['owner_id' => $this->owner->id]);
            Vehicle::factory()->archived()->create(['owner_id' => $this->owner->id]);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->select('[data-testid="status-filter"]', 'published')
                    ->pause(500)
                    ->assertElementCount('[data-testid="vehicle-card"]', 1)
                    ->select('[data-testid="status-filter"]', 'draft')
                    ->pause(500)
                    ->assertElementCount('[data-testid="vehicle-card"]', 1);
            });
        });

        it('can sort vehicles by different criteria', function () {
            Vehicle::factory()->create([
                'make' => 'BMW',
                'daily_rate' => 150,
                'owner_id' => $this->owner->id
            ]);
            Vehicle::factory()->create([
                'make' => 'Audi',
                'daily_rate' => 200,
                'owner_id' => $this->owner->id
            ]);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->select('[data-testid="sort-by"]', 'price_asc')
                    ->pause(500);

                // Check if BMW (lower price) appears before Audi
                $firstVehicle = $browser->element('[data-testid="vehicle-card"]:first-child [data-testid="vehicle-make"]');
                expect($firstVehicle->getText())->toBe('BMW');
            });
        });
    });

    describe('Vehicle Creation', function () {
        it('can create a new vehicle with all details', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->click('[data-testid="add-vehicle-btn"]')
                    ->assertPathIs('/admin/vehicles/create')
                    ->assertSee('Add New Vehicle')
                    ->type('[data-testid="make-input"]', 'Tesla')
                    ->type('[data-testid="model-input"]', 'Model S')
                    ->type('[data-testid="year-input"]', '2023')
                    ->select('[data-testid="fuel-type-select"]', 'electric')
                    ->select('[data-testid="transmission-select"]', 'automatic')
                    ->type('[data-testid="seating-capacity-input"]', '5')
                    ->type('[data-testid="daily-rate-input"]', '250')
                    ->type('[data-testid="description-textarea"]', 'Luxury electric sedan with autopilot')
                    ->select('[data-testid="owner-select"]', $this->owner->id)
                    ->click('[data-testid="submit-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/admin/vehicles')
                    ->assertSee('Vehicle created successfully')
                    ->assertSee('Tesla Model S');
            });
        });

        it('validates required fields during vehicle creation', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles/create')
                    ->click('[data-testid="submit-btn"]')
                    ->pause(1000)
                    ->assertSee('The make field is required')
                    ->assertSee('The model field is required')
                    ->assertSee('The year field is required')
                    ->assertSee('The daily rate field is required');
            });
        });

        it('can upload vehicle images during creation', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles/create')
                    ->type('[data-testid="make-input"]', 'BMW')
                    ->type('[data-testid="model-input"]', 'X5')
                    ->type('[data-testid="year-input"]', '2023')
                    ->select('[data-testid="fuel-type-select"]', 'petrol')
                    ->select('[data-testid="transmission-select"]', 'automatic')
                    ->type('[data-testid="daily-rate-input"]', '180')
                    ->select('[data-testid="owner-select"]', $this->owner->id)
                    ->attach('[data-testid="images-input"]', [
                        __DIR__ . '/../fixtures/test-car-1.jpg',
                        __DIR__ . '/../fixtures/test-car-2.jpg'
                    ])
                    ->pause(2000)
                    ->assertSee('2 images selected')
                    ->click('[data-testid="submit-btn"]')
                    ->pause(3000)
                    ->assertSee('Vehicle created successfully');
            });
        });
    });

    describe('Vehicle Editing and Updates', function () {
        it('can edit existing vehicle details', function () {
            $vehicle = Vehicle::factory()->create([
                'make' => 'Toyota',
                'model' => 'Camry',
                'owner_id' => $this->owner->id
            ]);

            browse(function ($browser) use ($vehicle) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->click("[data-testid='edit-vehicle-{$vehicle->id}']")
                    ->assertPathIs("/admin/vehicles/{$vehicle->id}/edit")
                    ->assertInputValue('[data-testid="make-input"]', 'Toyota')
                    ->clear('[data-testid="make-input"]')
                    ->type('[data-testid="make-input"]', 'Lexus')
                    ->clear('[data-testid="model-input"]')
                    ->type('[data-testid="model-input"]', 'ES 350')
                    ->click('[data-testid="update-btn"]')
                    ->pause(2000)
                    ->assertSee('Vehicle updated successfully')
                    ->assertSee('Lexus ES 350');
            });
        });

        it('can update vehicle status', function () {
            $vehicle = Vehicle::factory()->draft()->create([
                'owner_id' => $this->owner->id
            ]);

            browse(function ($browser) use ($vehicle) {
                $browser->loginAs($this->admin)
                    ->visit("/admin/vehicles/{$vehicle->id}/edit")
                    ->select('[data-testid="status-select"]', 'published')
                    ->click('[data-testid="update-btn"]')
                    ->pause(2000)
                    ->assertSee('Vehicle updated successfully');

                // Verify status change in listing
                $browser->visit('/admin/vehicles')
                    ->assertSee('Published');
            });
        });

        it('can add and remove vehicle images', function () {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);

            browse(function ($browser) use ($vehicle) {
                $browser->loginAs($this->admin)
                    ->visit("/admin/vehicles/{$vehicle->id}/edit")
                    ->attach('[data-testid="images-input"]', [
                        __DIR__ . '/../fixtures/test-car-1.jpg'
                    ])
                    ->pause(2000)
                    ->assertSee('1 new image selected')
                    ->click('[data-testid="update-btn"]')
                    ->pause(2000)
                    ->assertSee('Vehicle updated successfully');

                // Check if image was added
                $browser->refresh()
                    ->assertPresent('[data-testid="existing-image"]')
                    ->click('[data-testid="remove-image-btn"]:first')
                    ->pause(500)
                    ->assertSee('Image marked for removal')
                    ->click('[data-testid="update-btn"]')
                    ->pause(2000)
                    ->assertSee('Vehicle updated successfully');
            });
        });
    });

    describe('Vehicle Details and View', function () {
        it('displays vehicle details correctly', function () {
            $vehicle = Vehicle::factory()->create([
                'make' => 'Mercedes',
                'model' => 'C-Class',
                'year' => 2023,
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seating_capacity' => 5,
                'daily_rate' => 200,
                'description' => 'Luxury sedan with premium features',
                'owner_id' => $this->owner->id
            ]);

            browse(function ($browser) use ($vehicle) {
                $browser->loginAs($this->admin)
                    ->visit("/admin/vehicles/{$vehicle->id}")
                    ->assertSee('Mercedes C-Class')
                    ->assertSee('2023')
                    ->assertSee('Petrol')
                    ->assertSee('Automatic')
                    ->assertSee('5 seats')
                    ->assertSee('$200/day')
                    ->assertSee('Luxury sedan with premium features')
                    ->assertSee($this->owner->name);
            });
        });

        it('shows vehicle booking history', function () {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
            
            // Create some bookings for this vehicle
            \App\Models\Booking::factory(3)->create([
                'vehicle_id' => $vehicle->id,
                'renter_id' => $this->renter->id
            ]);

            browse(function ($browser) use ($vehicle) {
                $browser->loginAs($this->admin)
                    ->visit("/admin/vehicles/{$vehicle->id}")
                    ->assertSee('Booking History')
                    ->assertElementCount('[data-testid="booking-item"]', 3);
            });
        });
    });

    describe('Bulk Operations', function () {
        it('can perform bulk status updates', function () {
            $vehicles = Vehicle::factory(3)->draft()->create([
                'owner_id' => $this->owner->id
            ]);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->check('[data-testid="select-all-checkbox"]')
                    ->select('[data-testid="bulk-action-select"]', 'publish')
                    ->click('[data-testid="apply-bulk-action"]')
                    ->pause(2000)
                    ->assertSee('3 vehicles updated successfully')
                    ->assertSee('Published', 3);
            });
        });

        it('can bulk delete vehicles', function () {
            Vehicle::factory(5)->create(['owner_id' => $this->owner->id]);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->check('[data-testid="vehicle-checkbox"]:first')
                    ->check('[data-testid="vehicle-checkbox"]:nth-of-type(2)')
                    ->select('[data-testid="bulk-action-select"]', 'delete')
                    ->click('[data-testid="apply-bulk-action"]')
                    ->pause(1000)
                    ->assertSee('Are you sure you want to delete 2 vehicles?')
                    ->click('[data-testid="confirm-delete"]')
                    ->pause(2000)
                    ->assertSee('2 vehicles deleted successfully')
                    ->assertElementCount('[data-testid="vehicle-card"]', 3);
            });
        });
    });

    describe('Access Control and Permissions', function () {
        it('restricts vehicle creation to authorized users', function () {
            browse(function ($browser) {
                $browser->loginAs($this->renter)
                    ->visit('/admin/vehicles/create')
                    ->assertSee('Unauthorized')
                    ->assertPathIs('/admin/unauthorized');
            });
        });

        it('allows owners to edit only their vehicles', function () {
            $ownVehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
            $otherVehicle = Vehicle::factory()->create();

            browse(function ($browser) use ($ownVehicle, $otherVehicle) {
                $browser->loginAs($this->owner)
                    ->visit("/admin/vehicles/{$ownVehicle->id}/edit")
                    ->assertSee('Edit Vehicle')
                    ->visit("/admin/vehicles/{$otherVehicle->id}/edit")
                    ->assertSee('Unauthorized');
            });
        });
    });

    describe('Performance and User Experience', function () {
        it('loads vehicle list quickly with many vehicles', function () {
            Vehicle::factory(100)->create();

            $startTime = microtime(true);

            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->assertSee('Vehicle Management');
            });

            $loadTime = microtime(true) - $startTime;
            expect($loadTime)->toBeLessThan(3.0);
        });

        it('handles image upload progress correctly', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles/create')
                    ->attach('[data-testid="images-input"]', [
                        __DIR__ . '/../fixtures/large-image.jpg'
                    ])
                    ->pause(1000)
                    ->assertPresent('[data-testid="upload-progress"]')
                    ->waitFor('[data-testid="upload-complete"]', 10)
                    ->assertSee('Upload complete');
            });
        });

        it('provides real-time validation feedback', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/vehicles/create')
                    ->type('[data-testid="make-input"]', 'T')
                    ->pause(500)
                    ->assertDontSee('The make field is required')
                    ->clear('[data-testid="make-input"]')
                    ->pause(500)
                    ->assertSee('The make field is required');
            });
        });
    });
});