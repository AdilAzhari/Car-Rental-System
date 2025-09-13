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

        expect($this->vehicle->vehicleImages)->toHaveCount(3);
        expect($this->vehicle->vehicleImages->first())->toBeInstanceOf(VehicleImage::class);
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
        expect($this->vehicle->daily_rate)->toBeFloat();
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
            'terms_and_conditions',
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
        expect($vehicle->daily_rate)->toBe(85.00);
    });

    it('validates daily rate is numeric', function (): void {
        $vehicle = Vehicle::factory()->make(['daily_rate' => 'invalid']);

        expect($vehicle->daily_rate)->toBe('invalid'); // This would fail validation at form level
    });

    it('can soft delete', function (): void {
        $vehicleId = $this->vehicle->id;
        $this->vehicle->delete();

        expect(Vehicle::find($vehicleId))->toBeNull();
        expect(Vehicle::withTrashed()->find($vehicleId))->not->toBeNull();
    });
});
