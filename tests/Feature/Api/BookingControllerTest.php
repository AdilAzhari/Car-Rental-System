<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;

test('can create booking with visa payment', function (): void {
    $owner = User::factory()->create();
    $renter = User::factory()->create();

    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => true,
        'status' => 'published',
        'daily_rate' => 100,
    ]);

    $response = $this->actingAs($renter)
        ->postJson('/api/bookings', [
            'car_id' => $car->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'payment_method' => 'visa',
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'start_date',
                'end_date',
                'total_amount',
                'status',
                'payment_method',
                'vehicle',
                'renter',
            ],
        ])
        ->assertJson([
            'data' => [
                'status' => 'confirmed',
                'payment_method' => 'visa',
                'total_amount' => '300.00', // 3 days * $100
            ],
        ]);

    expect(Booking::count())->toBe(1);
});

test('can create booking with cash payment', function (): void {
    $owner = User::factory()->create();
    $renter = User::factory()->create();

    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => true,
        'status' => 'published',
        'daily_rate' => 50,
    ]);

    $response = $this->actingAs($renter)
        ->postJson('/api/bookings', [
            'car_id' => $car->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'payment_method' => 'cash',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'status' => 'pending',
                'payment_method' => 'cash',
                'total_amount' => '100.00', // 2 days * $50
            ],
        ]);
});

test('cannot book unavailable car', function (): void {
    $owner = User::factory()->create();
    $renter = User::factory()->create();

    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => false,
        'status' => 'published',
    ]);

    $response = $this->actingAs($renter)
        ->postJson('/api/bookings', [
            'car_id' => $car->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'payment_method' => 'visa',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['car_id']);
});

test('cannot book car with overlapping dates', function (): void {
    $owner = User::factory()->create();
    $renter1 = User::factory()->create();
    $renter2 = User::factory()->create();

    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => true,
        'status' => 'published',
    ]);

    // Create existing booking
    Booking::factory()->create([
        'renter_id' => $renter1->id,
        'vehicle_id' => $car->id,
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
        'status' => 'confirmed',
    ]);

    // Try to book overlapping dates
    $response = $this->actingAs($renter2)
        ->postJson('/api/bookings', [
            'car_id' => $car->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'payment_method' => 'visa',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['dates']);
});

test('can show booking to renter', function (): void {
    $owner = User::factory()->create();
    $renter = User::factory()->create();

    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => true,
        'status' => 'published',
    ]);

    $booking = Booking::factory()->create([
        'renter_id' => $renter->id,
        'vehicle_id' => $car->id,
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($renter)
        ->getJson("/api/bookings/{$booking->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'start_date',
                'end_date',
                'total_amount',
                'status',
                'vehicle' => [
                    'id',
                    'make',
                    'model',
                ],
                'renter' => [
                    'id',
                    'name',
                ],
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $booking->id,
            ],
        ]);
});

test('cannot show booking to unauthorized user', function (): void {
    $owner = User::factory()->create();
    $renter = User::factory()->create();
    $otherUser = User::factory()->create();

    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
    ]);

    $booking = Booking::factory()->create([
        'renter_id' => $renter->id,
        'vehicle_id' => $car->id,
    ]);

    $response = $this->actingAs($otherUser)
        ->getJson("/api/bookings/{$booking->id}");

    $response->assertNotFound();
});

test('booking requires authentication', function (): void {
    $response = $this->postJson('/api/bookings', [
        'car_id' => 1,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'payment_method' => 'visa',
    ]);

    $response->assertUnauthorized();
});
