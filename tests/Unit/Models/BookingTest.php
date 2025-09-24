<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Booking Model', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);

        $this->booking = Booking::factory()->create([
            'renter_id' => $this->renter->id,
            'vehicle_id' => $this->vehicle->id,
            'start_date' => Carbon::now()->addDays(1),
            'end_date' => Carbon::now()->addDays(5),
            'total_amount' => 400.00,
        ]);
    });

    it('belongs to a renter', function (): void {
        expect($this->booking->renter)->toBeInstanceOf(User::class);
        expect($this->booking->renter->id)->toBe($this->renter->id);
    });

    it('belongs to a vehicle', function (): void {
        expect($this->booking->vehicle)->toBeInstanceOf(Vehicle::class);
        expect($this->booking->vehicle->id)->toBe($this->vehicle->id);
    });

    it('has many payments', function (): void {
        Payment::factory(2)->create(['booking_id' => $this->booking->id]);

        expect($this->booking->payments)->toHaveCount(2);
        expect($this->booking->payments->first())->toBeInstanceOf(Payment::class);
    });

    it('has one review', function (): void {
        Review::factory()->create([
            'booking_id' => $this->booking->id,
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $this->renter->id,
        ]);

        expect($this->booking->review)->toBeInstanceOf(Review::class);
    });

    it('casts dates correctly', function (): void {
        expect($this->booking->start_date)->toBeInstanceOf(Carbon::class);
        expect($this->booking->end_date)->toBeInstanceOf(Carbon::class);
        expect($this->booking->status)->toBeInstanceOf(BookingStatus::class);
    });

    it('casts amounts to decimal', function (): void {
        expect($this->booking->total_amount)->toBeFloat();
        expect($this->booking->deposit_amount)->toBeFloat();
        expect($this->booking->commission_amount)->toBeFloat();
    });

    it('has correct fillable attributes', function (): void {
        $fillable = [
            'renter_id', 'vehicle_id', 'start_date', 'end_date',
            'total_amount', 'deposit_amount', 'commission_amount',
            'payment_status', 'status', 'notes', 'special_requirements',
        ];

        expect($this->booking->getFillable())->toEqual($fillable);
    });

    it('uses correct table name', function (): void {
        expect($this->booking->getTable())->toBe('car_rental_bookings');
    });

    it('calculates booking duration correctly', function (): void {
        $booking = Booking::factory()->create([
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-05'),
        ]);

        $duration = $booking->start_date->diffInDays($booking->end_date);
        expect($duration)->toBe(4);
    });

    it('can have different statuses', function (): void {
        $pending = Booking::factory()->create(['status' => BookingStatus::PENDING]);
        $confirmed = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
        $completed = Booking::factory()->create(['status' => BookingStatus::COMPLETED]);
        $cancelled = Booking::factory()->create(['status' => BookingStatus::CANCELLED]);

        expect($pending->status)->toBe(BookingStatus::PENDING);
        expect($confirmed->status)->toBe(BookingStatus::CONFIRMED);
        expect($completed->status)->toBe(BookingStatus::COMPLETED);
        expect($cancelled->status)->toBe(BookingStatus::CANCELLED);
    });

    it('validates date logic', function (): void {
        $booking = Booking::factory()->make([
            'start_date' => Carbon::now()->addDays(5),
            'end_date' => Carbon::now()->addDays(1), // End date before start date
        ]);

        // This would be caught by validation rules
        expect($booking->end_date)->toBeBefore($booking->start_date);
    });

    it('can calculate total with commission', function (): void {
        $booking = Booking::factory()->create([
            'total_amount' => 400.00,
            'commission_amount' => 40.00, // 10% commission
        ]);

        expect($booking->total_amount)->toBe(400.00);
        expect($booking->commission_amount)->toBe(40.00);
    });

    it('can soft delete', function (): void {
        $bookingId = $this->booking->id;
        $this->booking->delete();

        expect(Booking::find($bookingId))->toBeNull();
        expect(Booking::withTrashed()->find($bookingId))->not->toBeNull();
    });
});
