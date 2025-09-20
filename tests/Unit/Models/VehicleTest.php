<?php

use App\Enums\VehicleFuelType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleTransmission;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Vehicle Model', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->owner()->create();
        $this->vehicle = Vehicle::factory()->create([
            'owner_id' => $this->user->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
            'daily_rate' => 100.00,
        ]);
    });

    it('belongs to an owner', function (): void {
        expect($this->vehicle->owner)->toBeInstanceOf(User::class);
        expect($this->vehicle->owner->id)->toBe($this->user->id);
    });

    it('has many vehicle images', function (): void {
        VehicleImage::factory(3)->create(['vehicle_id' => $this->vehicle->id]);

        expect($this->vehicle->images)->toHaveCount(3);
        expect($this->vehicle->images->first())->toBeInstanceOf(VehicleImage::class);
    });

    it('has many bookings', function (): void {
        $renter = User::factory()->renter()->create();
        Booking::factory(2)->create([
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $renter->id,
        ]);

        expect($this->vehicle->bookings)->toHaveCount(2);
        expect($this->vehicle->bookings->first())->toBeInstanceOf(Booking::class);
    });

    it('has many reviews', function (): void {
        $renter = User::factory()->renter()->create();
        Review::factory(2)->create([
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $renter->id,
        ]);

        expect($this->vehicle->reviews)->toHaveCount(2);
        expect($this->vehicle->reviews->first())->toBeInstanceOf(Review::class);
    });

    it('casts attributes correctly', function (): void {
        expect($this->vehicle->daily_rate)->toBeString(); // decimal:2 returns string
        expect($this->vehicle->year)->toBeInt();
        expect($this->vehicle->fuel_type)->toBeInstanceOf(VehicleFuelType::class);
        expect($this->vehicle->transmission)->toBeInstanceOf(VehicleTransmission::class);
        expect($this->vehicle->status)->toBeInstanceOf(VehicleStatus::class);
    });

    it('has correct fillable attributes', function (): void {
        $fillable = [
            'owner_id', 'make', 'model', 'year', 'color', 'plate_number', 'vin',
            'fuel_type', 'transmission', 'seats', 'daily_rate', 'description',
            'status', 'is_available', 'location', 'mileage', 'insurance_expiry',
            'oil_type', 'last_oil_change', 'policy', 'category', 'doors',
            'engine_size', 'pickup_location', 'insurance_included',
            'featured_image', 'gallery_images', 'documents', 'features',
            'terms_and_conditions', 'traffic_violations', 'violations_last_checked',
            'total_violations_count', 'total_fines_amount', 'has_pending_violations',
        ];

        expect($this->vehicle->getFillable())->toEqual($fillable);
    });

    it('uses correct table name', function (): void {
        expect($this->vehicle->getTable())->toBe('car_rental_vehicles');
    });

    it('can create vehicle with required attributes', function (): void {
        $vehicleData = [
            'owner_id' => $this->user->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2023,
            'color' => 'Blue',
            'plate_number' => 'ABC-1234',
            'vin' => '1HGCM82633A123456',
            'fuel_type' => VehicleFuelType::PETROL->value,
            'transmission' => VehicleTransmission::AUTOMATIC->value,
            'seats' => 5,
            'daily_rate' => 85.00,
            'description' => 'Test Honda Civic',
            'status' => VehicleStatus::PUBLISHED->value,
            'is_available' => true,
            'location' => 'Kuala Lumpur',
            'mileage' => 25000,
            'insurance_expiry' => now()->addMonths(12)->format('Y-m-d'),
        ];

        $vehicle = Vehicle::create($vehicleData);

        expect($vehicle)->toBeInstanceOf(Vehicle::class);
        expect($vehicle->make)->toBe('Honda');
        expect($vehicle->model)->toBe('Civic');
        expect($vehicle->daily_rate)->toBe('85.00'); // decimal:2 returns string
    });

    it('handles invalid daily rate gracefully', function (): void {
        expect(function () {
            Vehicle::factory()->create(['daily_rate' => 'invalid']);
        })->toThrow(\Exception::class);
    });

    it('can soft delete', function (): void {
        $vehicleId = $this->vehicle->id;
        $this->vehicle->delete();

        expect(Vehicle::find($vehicleId))->toBeNull();
        expect(Vehicle::withTrashed()->find($vehicleId))->not->toBeNull();
    });

    describe('Featured Image URL Generation', function (): void {
        it('returns primary image from images relationship when loaded', function (): void {
            VehicleImage::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'image_path' => 'vehicles/primary.jpg',
                'is_primary' => true
            ]);

            VehicleImage::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'image_path' => 'vehicles/secondary.jpg',
                'is_primary' => false
            ]);

            $this->vehicle->load('images');
            $url = $this->vehicle->getFeaturedImageUrl();

            expect($url)->toBe('/storage/vehicles/primary.jpg');
        });

        it('returns first image when no primary image exists', function (): void {
            VehicleImage::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'image_path' => 'vehicles/first.jpg',
                'is_primary' => false
            ]);

            $this->vehicle->load('images');
            $url = $this->vehicle->getFeaturedImageUrl();

            expect($url)->toBe('/storage/vehicles/first.jpg');
        });

        it('falls back to featured_image field', function (): void {
            $vehicle = Vehicle::factory()->create([
                'owner_id' => $this->user->id,
                'featured_image' => 'vehicles/featured.jpg'
            ]);

            $url = $vehicle->getFeaturedImageUrl();

            expect($url)->toBe('/storage/vehicles/featured.jpg');
        });

        it('falls back to first gallery image', function (): void {
            $vehicle = Vehicle::factory()->create([
                'owner_id' => $this->user->id,
                'featured_image' => null,
                'gallery_images' => ['vehicles/gallery1.jpg', 'vehicles/gallery2.jpg']
            ]);

            $url = $vehicle->getFeaturedImageUrl();

            expect($url)->toBe('/storage/vehicles/gallery1.jpg');
        });

        it('returns null when no images available', function (): void {
            $vehicle = Vehicle::factory()->create([
                'owner_id' => $this->user->id,
                'featured_image' => null,
                'gallery_images' => null
            ]);

            expect($vehicle->getFeaturedImageUrl())->toBeNull();
        });
    });

    describe('Query Scopes', function (): void {
        beforeEach(function (): void {
            $this->owner = User::factory()->owner()->create();
        });

        it('filters available vehicles for rent', function (): void {
            Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'is_available' => true,
                'status' => VehicleStatus::PUBLISHED->value,
                'insurance_expiry' => now()->addYear()
            ]);

            Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'is_available' => false,
                'status' => VehicleStatus::PUBLISHED->value,
                'insurance_expiry' => now()->addYear()
            ]);

            $availableVehicles = Vehicle::availableForRent()->get();

            expect($availableVehicles)->toHaveCount(1);
            expect($availableVehicles->first()->is_available)->toBeTrue();
        });

        it('filters vehicles by owner', function (): void {
            Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'status' => VehicleStatus::PUBLISHED->value
            ]);

            $otherOwner = User::factory()->owner()->create();
            Vehicle::factory()->create([
                'owner_id' => $otherOwner->id,
                'status' => VehicleStatus::PUBLISHED->value
            ]);

            $ownerVehicles = Vehicle::byOwner($this->owner->id)->get();

            expect($ownerVehicles)->toHaveCount(1);
            expect($ownerVehicles->first()->owner_id)->toBe($this->owner->id);
        });

        it('filters by price range', function (): void {
            $vehicle1 = Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'daily_rate' => 50
            ]);

            $vehicle2 = Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'daily_rate' => 100
            ]);

            $vehicle3 = Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'daily_rate' => 200
            ]);

            $midRangeVehicles = Vehicle::priceRange(90, 110)->where('owner_id', $this->owner->id)->get();

            expect($midRangeVehicles)->toHaveCount(1); // Only vehicle2 ($100) in range
            expect($midRangeVehicles->first()->id)->toBe($vehicle2->id);
        });

        it('excludes vehicles with conflicting bookings', function (): void {
            $vehicle1 = Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'is_available' => true,
                'status' => VehicleStatus::PUBLISHED->value,
                'insurance_expiry' => now()->addYear()
            ]);

            $vehicle2 = Vehicle::factory()->create([
                'owner_id' => $this->owner->id,
                'is_available' => true,
                'status' => VehicleStatus::PUBLISHED->value,
                'insurance_expiry' => now()->addYear()
            ]);

            $renter = User::factory()->renter()->create();

            // Create booking that conflicts with our search dates
            Booking::factory()->create([
                'vehicle_id' => $vehicle1->id,
                'renter_id' => $renter->id,
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(7),
                'status' => 'confirmed'
            ]);

            $availableVehicles = Vehicle::availableForRent(
                now()->addDays(6)->toDateString(),
                now()->addDays(8)->toDateString()
            )->get();

            expect($availableVehicles)->toHaveCount(1);
            expect($availableVehicles->first()->id)->toBe($vehicle2->id);
        });
    });
});
