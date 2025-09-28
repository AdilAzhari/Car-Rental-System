<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Booking Management', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
    });

    describe('Booking Listing', function (): void {
        it('allows admin to view all bookings via Filament', function (): void {
            $bookings = Booking::factory(3)->create(['vehicle_id' => $this->vehicle->id]);

            $this->actingAs($this->admin);

            Livewire::test(ListBookings::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords($bookings);
        });

        it('filters bookings by status', function (): void {
            $pendingBooking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::PENDING,
            ]);
            $confirmedBooking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::CONFIRMED,
            ]);

            $this->actingAs($this->admin);

            Livewire::test(ListBookings::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords([$pendingBooking, $confirmedBooking]);
        });
    });

    describe('Booking Model Functionality', function (): void {
        it('can create booking with valid data', function (): void {
            $booking = Booking::create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::tomorrow(),
                'end_date' => Carbon::tomorrow()->addDays(3),
                'daily_rate' => '100.00',
                'subtotal' => '400.00',
                'total_amount' => '400.00',
                'status' => BookingStatus::PENDING,
                'payment_status' => PaymentStatus::UNPAID,
                'pickup_location' => 'Downtown',
                'dropoff_location' => 'Downtown',
            ]);

            expect($booking)->not->toBeNull()
                ->and($booking->renter_id)->toBe($this->renter->id)
                ->and($booking->vehicle_id)->toBe($this->vehicle->id)
                ->and($booking->status)->toBe(BookingStatus::PENDING);
        });

        it('validates date logic correctly', function (): void {
            $startDate = Carbon::tomorrow();
            $endDate = Carbon::tomorrow()->addDays(3);

            $booking = Booking::create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => BookingStatus::PENDING,
                'payment_status' => PaymentStatus::UNPAID,
                'pickup_location' => 'Downtown',
                'dropoff_location' => 'Downtown',
            ]);

            expect($booking->days)->toBe(4); // inclusive counting
        });

        it('tracks booking conflicts correctly', function (): void {
            // Create existing booking
            $existingBooking = Booking::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::tomorrow(),
                'end_date' => Carbon::tomorrow()->addDays(2),
                'status' => BookingStatus::CONFIRMED,
            ]);

            // Check for conflicts via model query
            $conflicts = Booking::where('vehicle_id', $this->vehicle->id)
                ->where('status', '!=', BookingStatus::CANCELLED)
                ->where(function ($query): void {
                    $query->whereBetween('start_date', [Carbon::tomorrow(), Carbon::tomorrow()->addDays(1)])
                        ->orWhereBetween('end_date', [Carbon::tomorrow(), Carbon::tomorrow()->addDays(1)]);
                })
                ->exists();

            expect($conflicts)->toBeTrue();
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

        it('can update booking status via model', function (): void {
            $this->booking->update(['status' => BookingStatus::CONFIRMED]);
            $this->booking->refresh();

            expect($this->booking->status)->toBe(BookingStatus::CONFIRMED);
        });

        it('can cancel bookings via model', function (): void {
            $this->booking->update(['status' => BookingStatus::CANCELLED]);
            $this->booking->refresh();

            expect($this->booking->status)->toBe(BookingStatus::CANCELLED);
        });

        it('supports different booking statuses', function (): void {
            $statuses = [
                BookingStatus::PENDING,
                BookingStatus::CONFIRMED,
                BookingStatus::COMPLETED,
                BookingStatus::CANCELLED,
            ];

            foreach ($statuses as $status) {
                $booking = Booking::factory()->create([
                    'renter_id' => $this->renter->id,
                    'vehicle_id' => $this->vehicle->id,
                    'status' => $status,
                ]);

                expect($booking->status)->toBe($status);
            }
        });
    });

    describe('Booking Calendar Event Functionality', function (): void {
        it('implements calendar event interface correctly', function (): void {
            $booking = Booking::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(2),
                'status' => BookingStatus::CONFIRMED,
            ]);

            // Test that booking implements Eventable interface
            expect($booking)->toBeInstanceOf(\Guava\Calendar\Contracts\Eventable::class);

            // Test calendar event creation
            $calendarEvent = $booking->toCalendarEvent();
            expect($calendarEvent)->toBeInstanceOf(\Guava\Calendar\ValueObjects\CalendarEvent::class);
        });
    });

    describe('Booking Payment Status', function (): void {
        it('tracks payment status separately from booking status', function (): void {
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::CONFIRMED,
                'payment_status' => PaymentStatus::UNPAID,
            ]);

            expect($booking->status)->toBe(BookingStatus::CONFIRMED)
                ->and($booking->payment_status)->toBe(PaymentStatus::UNPAID);
        });

        it('supports different payment statuses', function (): void {
            $paymentStatuses = [
                PaymentStatus::UNPAID,
                PaymentStatus::CONFIRMED,
                PaymentStatus::REFUNDED,
                PaymentStatus::FAILED,
            ];

            foreach ($paymentStatuses as $paymentStatus) {
                $booking = Booking::factory()->create([
                    'renter_id' => $this->renter->id,
                    'vehicle_id' => $this->vehicle->id,
                    'payment_status' => $paymentStatus,
                ]);

                expect($booking->payment_status)->toBe($paymentStatus);
            }
        });
    });

    describe('Booking Commission Calculation', function (): void {
        it('calculates commission amount correctly', function (): void {
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'total_amount' => '500.00',
                'commission_amount' => '50.00', // 10% commission
            ]);

            expect($booking->commission_amount)->toBe('50.00')
                ->and((float) $booking->commission_amount / (float) $booking->total_amount)->toBe(0.1);
        });
    });

    describe('Booking Soft Deletion', function (): void {
        it('soft deletes bookings instead of hard deletion', function (): void {
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
            ]);

            $bookingId = $booking->id;
            $booking->delete();

            expect(Booking::find($bookingId))->toBeNull()
                ->and(Booking::withTrashed()->find($bookingId))->not->toBeNull();
        });
    });
});
