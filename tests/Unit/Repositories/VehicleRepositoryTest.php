<?php

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use Illuminate\Http\Request;

describe('VehicleRepository', function (): void {
    beforeEach(function (): void {
        $this->repository = app(VehicleRepository::class);
        $this->owner = User::factory()->create();
        $this->renter = User::factory()->create();
    });

    it('can search vehicles with filters', function (): void {
        $vehicle1 = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'seats' => 5,
            'daily_rate' => 100,
        ]);

        $vehicle2 = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'transmission' => 'manual',
            'fuel_type' => 'hybrid',
            'seats' => 7,
            'daily_rate' => 150,
        ]);

        $request = new Request([
            'transmission' => 'automatic',
            'price_min' => 50,
            'price_max' => 120,
            'per_page' => 10,
        ]);

        $results = $this->repository->searchWithFilters($request);

        expect($results->items())->toHaveCount(1);
        expect($results->items()[0]->id)->toBe($vehicle1->id);
    });

    it('can find vehicle with details', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        Review::factory()->create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => $this->renter->id,
            'rating' => 5,
        ]);

        $result = $this->repository->findWithDetails($vehicle->id);

        expect($result->id)->toBe($vehicle->id);
        expect($result->relationLoaded('owner'))->toBeTrue();
        expect($result->relationLoaded('reviews'))->toBeTrue();
        expect($result->reviews_avg_rating)->toBeFloat();
        expect($result->reviews_count)->toBe(1);
    });

    it('can check vehicle availability', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        // Create conflicting booking
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => $this->renter->id,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-15',
            'status' => 'confirmed',
        ]);

        // Check availability for overlapping dates
        $isAvailable = $this->repository->checkAvailability(
            $vehicle->id,
            '2025-01-12',
            '2025-01-17'
        );

        expect($isAvailable)->toBeFalse();

        // Check availability for non-overlapping dates
        $isAvailableAfter = $this->repository->checkAvailability(
            $vehicle->id,
            '2025-01-20',
            '2025-01-25'
        );

        expect($isAvailableAfter)->toBeTrue();
    });

    it('can get owner vehicles', function (): void {
        Vehicle::factory(3)->create(['owner_id' => $this->owner->id]);
        Vehicle::factory(2)->create(); // Different owner

        $vehicles = $this->repository->getOwnerVehicles($this->owner->id);

        expect($vehicles)->toHaveCount(3);
        expect($vehicles->first()->owner_id)->toBe($this->owner->id);
    });

    it('can get vehicle statistics', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        // Create bookings
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => $this->renter->id,
            'status' => 'completed',
            'total_amount' => 500,
        ]);

        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => $this->renter->id,
            'status' => 'pending',
            'total_amount' => 300,
        ]);

        // Create reviews
        Review::factory()->create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => $this->renter->id,
            'rating' => 4.5,
        ]);

        $stats = $this->repository->getVehicleStatistics($vehicle->id);

        expect($stats)->toHaveKeys([
            'total_bookings', 'completed_bookings',
            'total_reviews', 'average_rating',
            'total_revenue', 'occupancy_rate',
        ]);
        expect($stats['total_bookings'])->toBe(2);
        expect($stats['completed_bookings'])->toBe(1);
        expect($stats['total_reviews'])->toBe(1);
        expect($stats['total_revenue'])->toBe(800);
    });

    it('can get vehicles needing attention', function (): void {
        // Vehicle with expired insurance
        $expiredInsurance = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'insurance_expiry' => now()->subDays(5),
        ]);

        // Vehicle with low rating
        $lowRatedVehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        Review::factory()->create([
            'vehicle_id' => $lowRatedVehicle->id,
            'renter_id' => $this->renter->id,
            'rating' => 1,
        ]);

        // Vehicle with no issues
        Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'insurance_expiry' => now()->addYear(),
        ]);

        $needingAttention = $this->repository->getVehiclesNeedingAttention($this->owner->id);

        expect($needingAttention)->toHaveCount(2);
        expect($needingAttention->pluck('id'))
            ->toContain($expiredInsurance->id)
            ->toContain($lowRatedVehicle->id);
    });

    it('can get popular vehicles', function (): void {
        $popular = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $unpopular = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        // Create more bookings for popular vehicle
        Booking::factory(5)->create(['vehicle_id' => $popular->id]);
        Booking::factory(1)->create(['vehicle_id' => $unpopular->id]);

        $vehicles = $this->repository->getPopularVehicles(5);

        expect($vehicles->first()->id)->toBe($popular->id);
        expect($vehicles->first()->bookings_count)->toBeGreaterThan(
            $vehicles->last()->bookings_count ?? 0
        );
    });
});
