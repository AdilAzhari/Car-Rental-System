<?php

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Vehicle;

/**
 * Demonstration of the powerful features we just implemented:
 * 1. Rate Limiting API Endpoints
 * 2. Eloquent Scopes for Complex Queries
 * 3. Pest Parallel Testing with Performance Monitoring
 */
describe('Power Features Demo', function (): void {

    beforeEach(function (): void {
        // Create test data for demonstrations
        $this->admin = createTestUser('admin');
        $this->owner = createTestUser('owner');
        $this->renter = createTestUser('renter');

        $this->vehicle = createTestVehicle([
            'owner_id' => $this->owner->id,
            'status' => VehicleStatus::PUBLISHED,
            'is_available' => true,
            'daily_rate' => 100.00,
            'has_pending_violations' => true,
            'traffic_violations' => [
                ['type' => 'speeding', 'status' => 'pending', 'fine' => 300],
                ['type' => 'parking', 'status' => 'resolved', 'fine' => 50],
            ],
        ]);
    });

    describe('Rate Limiting Tests', function (): void {

        test('API rate limits work for different user roles')
            ->group('parallel', 'api')
            ->expect(function (): true {
                // Test guest rate limits (30/minute)
                for ($i = 0; $i < 5; $i++) {
                    $response = $this->get('/api/cars');
                    expect($response->status())->toBe(200);
                }

                // Test authenticated renter limits (100/minute for high-freq ops)
                $this->actingAs($this->renter, 'sanctum');
                for ($i = 0; $i < 10; $i++) {
                    $response = $this->get('/api/favorites');
                    expect($response->status())->toBe(200);
                }

                // Verify rate limit headers are present
                $response = $this->get('/api/favorites');
                expect($response->headers->get('X-RateLimit-Limit'))->not->toBeNull();
                expect($response->headers->get('X-RateLimit-Remaining'))->not->toBeNull();

                return true;
            })->toBeTrue();

        test('critical operations have stricter rate limits')
            ->group('parallel', 'api')
            ->expect(function (): true {
                $this->actingAs($this->renter, 'sanctum');

                // Financial operations are limited to 10/minute
                $response = $this->post('/api/payments/intent', [
                    'booking_id' => 1,
                    'amount' => 100,
                ]);

                // Should have stricter limits
                expect($response->headers->get('X-RateLimit-Limit'))->toBeLessThanOrEqual('10');

                return true;
            })->toBeTrue();

    });

    describe('Eloquent Scopes Tests', function (): void {

        test('forRole scope filters vehicles correctly')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                // Admin sees everything
                $adminVehicles = Vehicle::forRole('admin')->get();
                expect($adminVehicles)->toHaveCount(1);

                // Owner sees only their vehicles
                $ownerVehicles = Vehicle::forRole('owner', $this->owner->id)->get();
                expect($ownerVehicles)->toHaveCount(1);
                expect($ownerVehicles->first()->owner_id)->toBe($this->owner->id);

                // Renter sees only published available vehicles
                $renterVehicles = Vehicle::forRole('renter')->get();
                expect($renterVehicles)->toHaveCount(1);
                expect($renterVehicles->first()->status->value)->toBe('published');
                expect($renterVehicles->first()->is_available)->toBeTrue();

                return true;
            })->toBeTrue();

        test('advanced search scope with multiple filters')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                $filters = [
                    'make' => $this->vehicle->make,
                    'min_price' => 50,
                    'max_price' => 150,
                    'transmission' => 'automatic',
                ];

                $results = Vehicle::advancedSearch($filters)->get();
                expect($results)->not->toBeEmpty();
                expect($results->first()->daily_rate)->toBeWithinRange(50, 150);

                return true;
            })->toBeTrue();

        test('popular vehicles scope with complex calculations')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                // Create some bookings and reviews for popularity calculation
                createTestBooking(['vehicle_id' => $this->vehicle->id, 'status' => BookingStatus::COMPLETED]);

                $popularVehicles = Vehicle::popular(5)->get();
                expect($popularVehicles->first())->toHaveKey('bookings_count');

                return true;
            })->toBeTrue();

        test('requiring attention scope finds problematic vehicles')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                // Our test vehicle has pending violations
                $attentionRequired = Vehicle::requiringAttention()->get();
                expect($attentionRequired)->toHaveCount(1);
                expect($attentionRequired->first()->has_pending_violations)->toBeTrue();

                return true;
            })->toBeTrue();

        test('booking scopes work with complex conditions')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                // Create test bookings
                $booking = createTestBooking([
                    'renter_id' => $this->renter->id,
                    'vehicle_id' => $this->vehicle->id,
                    'status' => BookingStatus::CONFIRMED,
                    'start_date' => now()->addDays(5),
                    'end_date' => now()->addDays(10),
                ]);

                // Test upcoming scope
                $upcomingBookings = Booking::upcoming(30)->get();
                expect($upcomingBookings)->toHaveCount(1);
                expect($upcomingBookings->first()->status->value)->toBe('confirmed');

                // Test role-based filtering
                $renterBookings = Booking::byStatus('confirmed', 'renter')->get();
                expect($renterBookings)->toHaveCount(1);

                return true;
            })->toBeTrue();
    });

    describe('Performance Monitoring', function (): void {

        test('query performance meets requirements')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                assertPerformance(fn () => Vehicle::with(['owner', 'bookings'])
                    ->advancedSearch(['category' => 'luxury'])
                    ->popular(10)
                    ->get(), 500, 20); // Max 500ms, 20MB memory

                return true;
            })->toBeTrue();

        test('scope chaining performance')
            ->group('parallel', 'fast')
            ->expect(function (): true {
                $metrics = measureExecutionTime(fn () => Vehicle::forRole('renter')
                    ->availableForRent(now(), now()->addDays(7))
                    ->advancedSearch(['min_price' => 50])
                    ->nearby('Kuala Lumpur')
                    ->popular(5)
                    ->get());

                expect($metrics['execution_time'])->toBeLessThan(1000); // Under 1 second
                expect($metrics['memory_used'])->toBeLessThan(10 * 1024 * 1024); // Under 10MB

                return true;
            })->toBeTrue();
    });

    describe('Integration Test', function (): void {

        test('all features work together seamlessly')
            ->group('database')
            ->expect(function (): true {
                // Test rate limiting + scopes + performance
                $this->actingAs($this->renter, 'sanctum');

                $metrics = measureExecutionTime(function () {
                    // Make API calls that use our scopes
                    $response = $this->get('/api/cars');
                    expect($response->status())->toBe(200);

                    // Verify rate limit headers
                    expect($response->headers->get('X-RateLimit-Limit'))->not->toBeNull();

                    return $response->json();
                });

                // Performance should be good even with complex scopes
                expect($metrics['execution_time'])->toBeLessThan(2000);

                // Database should have expected records
                expectDatabaseHasVehicle(['id' => $this->vehicle->id]);
                expectDatabaseCount('car_rental_vehicles', 1);

                return true;
            })->toBeTrue();
    });

});
