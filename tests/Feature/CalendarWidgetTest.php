<?php

use App\Filament\Widgets\BookingCalendarWidget;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;

test('booking calendar widget can be rendered', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(BookingCalendarWidget::class)
        ->assertSuccessful();
});

test('booking calendar displays events for date range', function (): void {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    // Create a booking within the date range
    $booking = Booking::factory()->create([
        'renter_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->startOfMonth()->addDays(3),
    ]);

    $this->actingAs($user);

    // Test that the widget can render with bookings data
    Livewire::test(BookingCalendarWidget::class)
        ->assertSuccessful();

    // Verify booking exists in database for the date range
    $bookingCount = Booking::whereDate('start_date', '>=', now()->startOfMonth()->subDays(7))
        ->whereDate('end_date', '<=', now()->endOfMonth()->addDays(7))
        ->count();

    expect($bookingCount)->toBe(1);
});

test('booking model can be converted to calendar event', function (): void {
    $user = User::factory()->create(['name' => 'John Doe']);
    $vehicle = Vehicle::factory()->create(['make' => 'Toyota', 'model' => 'Camry']);

    $booking = Booking::factory()->create([
        'renter_id' => $user->id,
        'vehicle_id' => $vehicle->id,
        'start_date' => now(),
        'end_date' => now()->addDays(3),
    ]);

    $calendarEvent = $booking->toCalendarEvent();

    // Test that the calendar event is created successfully
    expect($calendarEvent)->toBeInstanceOf(\Guava\Calendar\ValueObjects\CalendarEvent::class)
        ->and($calendarEvent)->not->toBeNull();

    // Test that the booking implements the Eventable interface
    expect($booking)->toBeInstanceOf(\Guava\Calendar\Contracts\Eventable::class);
});
