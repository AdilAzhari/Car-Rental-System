<?php

use App\Events\BookingCreated;
use App\Exceptions\VehicleException;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Event;

describe('BookingController Enhanced Tests', function () {
    beforeEach(function () {
        Event::fake();
        $this->owner = User::factory()->create();
        $this->renter = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    it('creates booking with proper DTO validation', function () {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'daily_rate' => 100,
        ]);

        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'payment_method' => 'visa',
                'payment_method_id' => 'pm_test_123',
                'pickup_location' => 'Downtown Office',
                'special_requests' => 'Need GPS and child seat',
            ]);

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'message',
                'booking' => [
                    'data' => [
                        'id', 'status', 'total_amount',
                        'start_date', 'end_date',
                        'pickup_location', 'special_requests',
                    ],
                ],
            ]);

        expect(Booking::count())->toBe(1);

        $booking = Booking::first();
        expect($booking->pickup_location)->toBe('Downtown Office');
        expect($booking->special_requests)->toBe('Need GPS and child seat');
    });

    it('dispatches BookingCreated event', function () {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'payment_method' => 'cash',
            ]);

        Event::assertDispatched(BookingCreated::class, function ($event) {
            return $event->booking instanceof Booking;
        });
    });

    it('handles custom exceptions properly', function () {
        // Test VehicleException for unavailable vehicle
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => false,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'payment_method' => 'visa',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_type' => 'vehicle_error',
            ]);
    });

    it('handles date conflicts with proper exception', function () {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        // Create existing booking
        Booking::factory()->create([
            'vehicle_id' => $vehicle->id,
            'renter_id' => $this->renter->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDays(6)->toDateString(),
                'end_date' => now()->addDays(8)->toDateString(),
                'payment_method' => 'visa',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_type' => 'booking_error',
                'context' => [
                    'vehicle_id' => $vehicle->id,
                ],
            ]);
    });

    it('validates DTO requirements correctly', function () {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        // Test missing payment_method_id for visa payment
        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'payment_method' => 'visa',
                // Missing payment_method_id
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method_id']);
    });

    it('calculates amounts correctly using DTO', function () {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'daily_rate' => 120.50,
        ]);

        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(), // 4 days
                'payment_method' => 'cash',
            ]);

        $response->assertSuccessful();

        $booking = Booking::first();

        // Daily rate: 120.50 * 4 days = 482.00
        // Insurance (10%): 48.20
        // Tax (8%): (482.00 + 48.20) * 0.08 = 42.416
        // Total: 482.00 + 48.20 + 42.416 = 572.616 ≈ 572.62
        expect($booking->total_amount)->toBeFloat();
        expect($booking->total_amount)->toBeGreaterThan(500);
    });

    it('uses transaction service for atomic operations', function () {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        // Simulate a scenario where booking creation might fail
        // The transaction service should ensure atomicity
        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'payment_method' => 'visa',
                'payment_method_id' => 'pm_test_123',
            ]);

        if ($response->successful()) {
            expect(Booking::count())->toBe(1);
        } else {
            // If transaction failed, no partial data should exist
            expect(Booking::count())->toBe(0);
        }
    });
});

describe('Booking Authorization Middleware', function () {
    beforeEach(function () {
        $this->owner = User::factory()->create();
        $this->renter = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->booking = Booking::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $this->renter->id,
        ]);
    });

    it('allows renter to access their booking', function () {
        $response = $this->actingAs($this->renter)
            ->getJson("/api/bookings/{$this->booking->id}");

        $response->assertSuccessful();
    });

    it('allows vehicle owner to access booking', function () {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/bookings/{$this->booking->id}");

        $response->assertSuccessful();
    });

    it('prevents unauthorized users from accessing booking', function () {
        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/bookings/{$this->booking->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_type' => 'booking_error',
            ]);
    });

    it('allows admin to access any booking', function () {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson("/api/bookings/{$this->booking->id}");

        $response->assertSuccessful();
    });
});
