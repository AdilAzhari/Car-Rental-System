<?php

use App\Actions\Booking\CreateBookingAction;
use App\DTOs\CreateBookingDTO;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

describe('Architectural Integration Tests', function (): void {
    beforeEach(function (): void {
        Event::fake();
        $this->owner = User::factory()->create();
        $this->renter = User::factory()->create();
    });

    it('integrates repository pattern with search functionality', function (): void {
        // Create test vehicles
        Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'transmission' => 'automatic',
            'daily_rate' => 100,
        ]);

        Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'transmission' => 'manual',
            'daily_rate' => 80,
        ]);

        $vehicleRepository = app(VehicleRepository::class);
        $request = new Request([
            'transmission' => 'automatic',
            'price_min' => 90,
        ]);

        $lengthAwarePaginator = $vehicleRepository->searchWithFilters($request);

        expect($lengthAwarePaginator->items())->toHaveCount(1)
            ->and($lengthAwarePaginator->items()[0]->transmission)->toBe('automatic');
    });

    it('integrates DTO with action classes', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'daily_rate' => 100,
        ]);

        $dto = new CreateBookingDTO(
            carId: $vehicle->id,
            renterId: $this->renter->id,
            startDate: Carbon::tomorrow(),
            endDate: Carbon::tomorrow()->addDays(2),
            durationDays: 3,
            paymentMethod: 'cash',
            paymentMethodId: null,
            pickupLocation: 'Downtown',
            dropoffLocation: 'Airport',
            specialRequests: 'GPS needed'
        );

        $createBookingAction = app(CreateBookingAction::class);
        $booking = $createBookingAction->executeWithDTO($dto);

        expect($booking)->toBeInstanceOf(Booking::class)
            ->and($booking->pickup_location)->toBe('Downtown')
            ->and($booking->dropoff_location)->toBe('Airport')
            ->and($booking->special_requests)->toBe('GPS needed');
    });

    it('integrates event system with booking creation', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'payment_method' => 'cash',
            ]);

        $response->assertSuccessful();

        Event::assertDispatched(BookingCreated::class, fn($event): bool => $event->booking instanceof Booking &&
               $event->booking->payment_method === 'cash');
    });

    it('integrates transaction service with repository', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $transactionService = app(TransactionService::class);
        $vehicleRepository = app(VehicleRepository::class);

        // Test that repository calls work within transactions
        $result = $transactionService->safeExecute(fn() => $vehicleRepository->findWithDetails($vehicle->id));

        expect($result->id)->toBe($vehicle->id)
            ->and($result->relationLoaded('owner'))->toBeTrue();
    });

    it('integrates custom exceptions with middleware', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => false, // Not available
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_type' => 'vehicle_error',
                'context' => [
                    'vehicle_id' => $vehicle->id,
                ],
            ]);
    });

    it('integrates all architectural patterns in full booking flow', function (): void {
        // Setup
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
            'daily_rate' => 120.50,
        ]);

        // Full flow: API → FormRequest → DTO → Action → Repository → Transaction → Event
        $response = $this->actingAs($this->renter)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'payment_method' => 'visa',
                'payment_method_id' => 'pm_test_123',
                'pickup_location' => 'Main Office',
                'special_requests' => 'Child seat required',
            ]);

        // Verify API response
        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'message',
                'booking' => ['data'],
            ]);

        // Verify database state
        $booking = Booking::first();
        expect($booking)->not->toBeNull()
            ->and($booking->pickup_location)->toBe('Main Office')
            ->and($booking->special_requests)->toBe('Child seat required')
            ->and($booking->total_amount)->toBeFloat()
            ->and($booking->total_amount)->toBeGreaterThan(300);

        // Verify calculation (DTOs work)
        // 3 days * 120.50 + fees

        // Verify event was dispatched
        Event::assertDispatched(BookingCreated::class);

        // Verify repository methods work
        $vehicleRepository = app(VehicleRepository::class);
        $stats = $vehicleRepository->getVehicleStatistics($vehicle->id);
        expect($stats['total_bookings'])->toBe(1);
    });
});

describe('Performance Integration Tests', function (): void {
    it('handles concurrent booking requests correctly', function (): void {
        $vehicle = Vehicle::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $renter1 = User::factory()->create();
        $renter2 = User::factory()->create();

        // Simulate concurrent requests for the same dates
        $responses = [];

        $responses[] = $this->actingAs($renter1)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'payment_method' => 'cash',
            ]);

        $responses[] = $this->actingAs($renter2)
            ->postJson('/api/bookings', [
                'car_id' => $vehicle->id,
                'start_date' => now()->addDays(6)->toDateString(),
                'end_date' => now()->addDays(8)->toDateString(),
                'payment_method' => 'cash',
            ]);

        // One should succeed, one should fail due to date conflict
        $successCount = collect($responses)->filter(fn ($r) => $r->successful())->count();
        $conflictCount = collect($responses)->filter(fn ($r): bool => $r->status() === 409)->count();

        expect($successCount)->toBe(1)
            ->and($conflictCount)->toBe(1)
            ->and(Booking::query()->count())->toBe(1);
    });

    it('maintains performance under load', function (): void {
        // Create test data
        $owner = User::factory()->create();
        $vehicles = Vehicle::factory(10)->create([
            'owner_id' => $owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $renter = User::factory()->create();

        // Test repository performance
        assertPerformance(function () {
            $vehicleRepository = app(VehicleRepository::class);
            $request = new Request(['per_page' => 10]);

            return $vehicleRepository->searchWithFilters($request);
        }, 500, 10); // Max 500ms, 10MB memory
    });
});
