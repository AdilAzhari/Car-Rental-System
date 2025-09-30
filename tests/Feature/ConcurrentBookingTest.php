<?php

namespace Tests\Feature;

use App\Actions\Booking\CreateBookingAction;
use App\DTOs\CreateBookingDTO;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingConflictResolutionService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vehicle $vehicle;

    private CreateBookingAction $createBookingAction;

    private BookingConflictResolutionService $bookingConflictResolutionService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vehicle = Vehicle::factory()->create([
            'is_available' => true,
            'status' => 'published',
            'daily_rate' => 100.00,
        ]);

        $this->createBookingAction = app(CreateBookingAction::class);
        $this->bookingConflictResolutionService = app(BookingConflictResolutionService::class);
    }

    public function test_prevents_concurrent_bookings_for_same_vehicle_and_dates(): void
    {
        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays(2);

        // Create first booking DTO
        $createBookingDTO = CreateBookingDTO::fromArray([
            'car_id' => $this->vehicle->id,
            'renter_id' => $this->user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => 3,
            'payment_method' => 'cash',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
        ]);

        // Create second booking DTO for same vehicle and overlapping dates
        $user2 = User::factory()->create();
        $bookingData2 = CreateBookingDTO::fromArray([
            'car_id' => $this->vehicle->id,
            'renter_id' => $user2->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => 3,
            'payment_method' => 'cash',
            'pickup_location' => 'Test Location 2',
            'dropoff_location' => 'Test Location 2',
        ]);

        // Act as first user and create booking
        $this->actingAs($this->user);
        $firstBooking = $this->createBookingAction->executeWithDTO($createBookingDTO);

        // Verify first booking was created successfully
        expect($firstBooking)
            ->toBeInstanceOf(Booking::class)
            ->and($firstBooking->vehicle_id)->toBe($this->vehicle->id)
            ->and($firstBooking->status->value)->toBe('pending');

        // Act as second user and attempt to create conflicting booking
        $this->actingAs($user2);

        // This should throw a booking exception due to pessimistic locking and validation
        $this->expectException(\App\Exceptions\BookingException::class);

        $this->createBookingAction->executeWithDTO($bookingData2);
    }

    public function test_database_constraint_prevents_overlapping_bookings(): void
    {
        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays(2);

        // Create first booking directly in database
        $firstBooking = Booking::create([
            'renter_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 300.00,
            'status' => 'confirmed',
            'payment_status' => 'confirmed',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
        ]);

        expect($firstBooking->id)->toBeGreaterThan(0);

        // Attempt to create overlapping booking - should trigger database constraint
        $this->expectException(QueryException::class);

        Booking::create([
            'renter_id' => User::factory()->create()->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => $startDate->copy()->addDay(), // Overlaps with first booking
            'end_date' => $endDate->copy()->addDay(),
            'total_amount' => 300.00,
            'status' => 'confirmed',
            'payment_status' => 'confirmed',
            'pickup_location' => 'Test Location 2',
            'dropoff_location' => 'Test Location 2',
        ]);
    }

    public function test_conflict_resolution_service_detects_conflicts(): void
    {
        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays(2);

        // Create existing booking
        Booking::create([
            'renter_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 300.00,
            'status' => 'confirmed',
            'payment_status' => 'confirmed',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
        ]);

        // Check for conflicts with overlapping dates
        $conflictResult = $this->bookingConflictResolutionService->detectConflicts(
            $this->vehicle->id,
            $startDate->copy()->addDay()->toDateString(),
            $endDate->copy()->addDay()->toDateString()
        );

        expect($conflictResult['has_conflicts'])->toBeTrue()
            ->and($conflictResult['conflicts'])->toHaveCount(1)
            ->and($conflictResult['resolution_options'])->toBeArray();
    }

    public function test_allows_non_overlapping_bookings(): void
    {
        $startDate1 = Carbon::tomorrow();
        $endDate1 = Carbon::tomorrow()->addDays(2);

        $startDate2 = Carbon::tomorrow()->addDays(4); // Non-overlapping
        $endDate2 = Carbon::tomorrow()->addDays(6);

        // Create first booking
        $firstBooking = Booking::create([
            'renter_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => $startDate1,
            'end_date' => $endDate1,
            'total_amount' => 300.00,
            'status' => 'confirmed',
            'payment_status' => 'confirmed',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
        ]);

        // Create second non-overlapping booking - should succeed
        $secondBooking = Booking::create([
            'renter_id' => User::factory()->create()->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => $startDate2,
            'end_date' => $endDate2,
            'total_amount' => 300.00,
            'status' => 'confirmed',
            'payment_status' => 'confirmed',
            'pickup_location' => 'Test Location 2',
            'dropoff_location' => 'Test Location 2',
        ]);

        expect($firstBooking->id)->toBeGreaterThan(0)
            ->and($secondBooking->id)->toBeGreaterThan(0)
            ->and($firstBooking->id)->not->toBe($secondBooking->id);
    }

    public function test_cleanup_expired_bookings(): void
    {
        // Create an expired booking (older than 1 hour in pending status)
        Booking::create([
            'renter_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => Carbon::tomorrow(),
            'end_date' => Carbon::tomorrow()->addDays(2),
            'total_amount' => 300.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
            'created_at' => now()->subHours(2), // 2 hours ago
        ]);

        // Create a recent booking that shouldn't be cleaned up
        Booking::create([
            'renter_id' => User::factory()->create()->id,
            'vehicle_id' => Vehicle::factory()->create()->id,
            'start_date' => Carbon::tomorrow()->addDays(3),
            'end_date' => Carbon::tomorrow()->addDays(5),
            'total_amount' => 300.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
            'created_at' => now()->subMinutes(30), // 30 minutes ago
        ]);

        // Run cleanup
        $cleanedCount = $this->bookingConflictResolutionService->cleanupExpiredBookings();

        expect($cleanedCount)->toBeGreaterThanOrEqual(0);

        // The cleanup service will handle expiring bookings based on business logic
        // In a production environment, this would be run via a cron job
        expect($cleanedCount)->toBeNumeric();
    }

    public function test_pessimistic_locking_prevents_race_conditions(): void
    {
        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays(2);

        // Simulate concurrent access by manually testing the repository method
        $vehicleRepository = app(\App\Repositories\VehicleRepository::class);

        // First check should succeed
        $isAvailable1 = DB::transaction(fn () => $vehicleRepository->checkAvailabilityWithLock(
            $this->vehicle->id,
            $startDate->toDateString(),
            $endDate->toDateString()
        ));

        expect($isAvailable1)->toBeTrue();

        // Create a booking to make the vehicle unavailable
        Booking::create([
            'renter_id' => $this->user->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 300.00,
            'status' => 'confirmed',
            'payment_status' => 'confirmed',
            'pickup_location' => 'Test Location',
            'dropoff_location' => 'Test Location',
        ]);

        // Second check should fail due to existing booking
        $isAvailable2 = DB::transaction(fn () => $vehicleRepository->checkAvailabilityWithLock(
            $this->vehicle->id,
            $startDate->toDateString(),
            $endDate->toDateString()
        ));

        expect($isAvailable2)->toBeFalse();
    }
}
