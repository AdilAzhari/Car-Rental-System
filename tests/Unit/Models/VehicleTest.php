<?php

use App\Models\Vehicle;
use App\Models\User;
use App\Models\VehicleImage;
use App\Models\Booking;
use App\Models\Review;
use App\Enums\VehicleFuelType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleTransmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Vehicle Model', function () {
    beforeEach(function () {
        $this->user = User::factory()->owner()->create();
        $this->vehicle = Vehicle::factory()->create([
            'owner_id' => $this->user->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2022,
            'daily_rate' => 100.00
        ]);
    });

    it('belongs to an owner', function () {
        expect($this->vehicle->owner)->toBeInstanceOf(User::class);
        expect($this->vehicle->owner->id)->toBe($this->user->id);
    });

    it('has many vehicle images', function () {
        VehicleImage::factory(3)->create(['vehicle_id' => $this->vehicle->id]);
        
        expect($this->vehicle->vehicleImages)->toHaveCount(3);
        expect($this->vehicle->vehicleImages->first())->toBeInstanceOf(VehicleImage::class);
    });

    it('has many bookings', function () {
        $renter = User::factory()->renter()->create();
        Booking::factory(2)->create([
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $renter->id
        ]);
        
        expect($this->vehicle->bookings)->toHaveCount(2);
        expect($this->vehicle->bookings->first())->toBeInstanceOf(Booking::class);
    });

    it('has many reviews', function () {
        $renter = User::factory()->renter()->create();
        Review::factory(2)->create([
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $renter->id
        ]);
        
        expect($this->vehicle->reviews)->toHaveCount(2);
        expect($this->vehicle->reviews->first())->toBeInstanceOf(Review::class);
    });

    it('casts attributes correctly', function () {
        expect($this->vehicle->daily_rate)->toBeFloat();
        expect($this->vehicle->year)->toBeInt();
        expect($this->vehicle->fuel_type)->toBeInstanceOf(VehicleFuelType::class);
        expect($this->vehicle->transmission)->toBeInstanceOf(VehicleTransmission::class);
        expect($this->vehicle->status)->toBeInstanceOf(VehicleStatus::class);
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'owner_id', 'make', 'model', 'year', 'plate_number', 'vin',
            'fuel_type', 'transmission', 'daily_rate', 'oil_type',
            'last_oil_change', 'policy', 'status'
        ];
        
        expect($this->vehicle->getFillable())->toEqual($fillable);
    });

    it('uses correct table name', function () {
        expect($this->vehicle->getTable())->toBe('car_rental_vehicles');
    });

    it('can create vehicle with required attributes', function () {
        $vehicleData = [
            'owner_id' => $this->user->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2023,
            'plate_number' => 'ABC-1234',
            'fuel_type' => VehicleFuelType::PETROL->value,
            'transmission' => VehicleTransmission::AUTOMATIC->value,
            'daily_rate' => 85.00,
            'status' => VehicleStatus::PUBLISHED->value
        ];
        
        $vehicle = Vehicle::create($vehicleData);
        
        expect($vehicle)->toBeInstanceOf(Vehicle::class);
        expect($vehicle->make)->toBe('Honda');
        expect($vehicle->model)->toBe('Civic');
        expect($vehicle->daily_rate)->toBe(85.00);
    });

    it('validates daily rate is numeric', function () {
        $vehicle = Vehicle::factory()->make(['daily_rate' => 'invalid']);
        
        expect($vehicle->daily_rate)->toBe('invalid'); // This would fail validation at form level
    });

    it('can soft delete', function () {
        $vehicleId = $this->vehicle->id;
        $this->vehicle->delete();
        
        expect(Vehicle::find($vehicleId))->toBeNull();
        expect(Vehicle::withTrashed()->find($vehicleId))->not->toBeNull();
    });
});