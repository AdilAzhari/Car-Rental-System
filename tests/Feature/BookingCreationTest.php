<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('app');
    $this->actingAs(User::factory()->active()->admin()->create());
});

test('can create booking with calculated fields', function (): void {
    $vehicle = Vehicle::factory()->create([
        'daily_rate' => 100,
        'status' => 'published',
        'is_available' => true,
    ]);

    $renter = User::factory()->renter()->active()->create();

    $startDate = Carbon::today()->addDays(1);
    $endDate = Carbon::today()->addDays(3);

    $booking = Booking::create([
        'renter_id' => $renter->id,
        'vehicle_id' => $vehicle->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'days' => 2,
        'daily_rate' => 100,
        'subtotal' => 200,
        'insurance_fee' => 20, // 10% of subtotal
        'tax_amount' => 30, // 15% of subtotal
        'total_amount' => 250, // subtotal + insurance + tax
        'status' => 'pending',
        'payment_status' => 'pending',
        'pickup_location' => 'Downtown',
        'dropoff_location' => 'Downtown',
    ]);

    expect($booking)->not->toBeNull();
    expect($booking->renter_id)->toBe($renter->id);
    expect($booking->vehicle_id)->toBe($vehicle->id);
    expect($booking->days)->toBe(2);
    expect($booking->daily_rate)->toBe(100.00);
    expect($booking->subtotal)->toBe(200.00);
    expect($booking->insurance_fee)->toBe(20.00);
    expect($booking->tax_amount)->toBe(30.00);
    expect($booking->total_amount)->toBe(250.00);
});

test('booking model has working days attribute', function (): void {
    $startDate = Carbon::today();
    $endDate = Carbon::today()->addDays(5);

    $booking = Booking::factory()->create([
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    expect($booking->days)->toBe(6); // Should include both start and end dates
});

test('booking relationships work correctly', function (): void {
    $vehicle = Vehicle::factory()->create();
    $renter = User::factory()->renter()->active()->create();

    $booking = Booking::factory()->create([
        'vehicle_id' => $vehicle->id,
        'renter_id' => $renter->id,
    ]);

    expect($booking->vehicle)->not->toBeNull();
    expect($booking->vehicle->id)->toBe($vehicle->id);
    expect($booking->renter)->not->toBeNull();
    expect($booking->renter->id)->toBe($renter->id);
});
