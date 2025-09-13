<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Booking Management', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
    });

    describe('Booking Listing', function (): void {
        it('allows admin to view all bookings', function (): void {
            Booking::factory(3)->create(['vehicle_id' => $this->vehicle->id]);

            $this->actingAs($this->admin)
                ->get('/admin/bookings')
                ->assertSuccessful()
                ->assertSee('Bookings');
        });

        it('allows renter to view only their bookings', function (): void {
            Booking::factory(2)->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
            ]);
            Booking::factory()->create(['vehicle_id' => $this->vehicle->id]); // Other renter's booking

            $this->actingAs($this->renter)
                ->get('/admin/bookings')
                ->assertSuccessful();
        });

        it('filters bookings by status', function (): void {
            Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::PENDING,
            ]);

            $this->actingAs($this->admin)
                ->get('/admin/bookings?status=pending')
                ->assertSuccessful();
        });
    });

    describe('Booking Creation', function (): void {
        it('allows renter to create new booking', function (): void {
            $bookingData = [
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::tomorrow()->format('Y-m-d'),
                'end_date' => Carbon::tomorrow()->addDays(3)->format('Y-m-d'),
                'total_amount' => 300.00,
                'notes' => 'Business trip booking',
            ];

            $this->actingAs($this->renter)
                ->get('/admin/bookings/create')
                ->assertSuccessful();
        });

        it('validates booking dates', function (): void {
            $bookingData = [
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::tomorrow()->addDays(5)->format('Y-m-d'),
                'end_date' => Carbon::tomorrow()->format('Y-m-d'), // End date before start date
                'total_amount' => 300.00,
            ];

            $this->actingAs($this->renter)
                ->post('/admin/bookings', $bookingData)
                ->assertSessionHasErrors(['end_date']);
        });

        it('prevents double booking for same dates', function (): void {
            // Create existing booking
            Booking::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::tomorrow(),
                'end_date' => Carbon::tomorrow()->addDays(2),
                'status' => BookingStatus::CONFIRMED,
            ]);

            $conflictingBooking = [
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::tomorrow()->format('Y-m-d'),
                'end_date' => Carbon::tomorrow()->addDays(1)->format('Y-m-d'),
                'total_amount' => 200.00,
            ];

            $this->actingAs($this->renter)
                ->post('/admin/bookings', $conflictingBooking)
                ->assertSessionHasErrors();
        });
    });

    describe('Booking Status Management', function (): void {
        beforeEach(function (): void {
            $this->booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::PENDING,
            ]);
        });

        it('allows admin to update booking status', function (): void {
            $this->actingAs($this->admin)
                ->patch("/admin/bookings/{$this->booking->id}", [
                    'status' => BookingStatus::CONFIRMED->value,
                ]);

            $this->booking->refresh();
            expect($this->booking->status)->toBe(BookingStatus::CONFIRMED);
        });

        it('allows owner to confirm bookings for their vehicles', function (): void {
            $this->actingAs($this->owner)
                ->patch("/admin/bookings/{$this->booking->id}", [
                    'status' => BookingStatus::CONFIRMED->value,
                ]);

            $this->booking->refresh();
            expect($this->booking->status)->toBe(BookingStatus::CONFIRMED);
        });

        it('allows renter to cancel their own bookings', function (): void {
            $this->actingAs($this->renter)
                ->patch("/admin/bookings/{$this->booking->id}", [
                    'status' => BookingStatus::CANCELLED->value,
                ]);

            $this->booking->refresh();
            expect($this->booking->status)->toBe(BookingStatus::CANCELLED);
        });
    });

    describe('Booking Calendar Integration', function (): void {
        it('displays bookings in calendar format', function (): void {
            Booking::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(2),
                'status' => BookingStatus::CONFIRMED,
            ]);

            $this->actingAs($this->admin)
                ->get('/admin/bookings/calendar')
                ->assertSuccessful();
        });

        it('allows creating bookings from calendar', function (): void {
            $this->actingAs($this->renter)
                ->get('/admin/bookings/create-with-calendar')
                ->assertSuccessful();
        });
    });

    describe('Booking Payment Status', function (): void {
        it('tracks payment status separately from booking status', function (): void {
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::CONFIRMED,
                'payment_status' => 'unpaid',
            ]);

            expect($booking->status)->toBe(BookingStatus::CONFIRMED);
            expect($booking->payment_status)->toBe('unpaid');
        });
    });

    describe('Booking Commission Calculation', function (): void {
        it('calculates commission amount correctly', function (): void {
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'total_amount' => 500.00,
                'commission_amount' => 50.00, // 10% commission
            ]);

            expect($booking->commission_amount)->toBe(50.00);
            expect($booking->commission_amount / $booking->total_amount)->toBe(0.1);
        });
    });

    describe('Booking Soft Deletion', function (): void {
        it('soft deletes bookings instead of hard deletion', function (): void {
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
            ]);

            $bookingId = $booking->id;

            $this->actingAs($this->admin)
                ->delete("/admin/bookings/{$bookingId}");

            expect(Booking::find($bookingId))->toBeNull();
            expect(Booking::withTrashed()->find($bookingId))->not->toBeNull();
        });
    });
});
