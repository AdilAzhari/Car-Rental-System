<?php

use App\Models\User;
use App\Models\Vehicle;

test('can list available cars', function (): void {
    // Create a user to own the car
    $owner = User::factory()->create();

    // Create available cars
    Vehicle::factory()->count(3)->create([
        'owner_id' => $owner->id,
        'is_available' => true,
        'status' => 'published',
    ]);

    // Create unavailable car (should not appear in results)
    Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => false,
        'status' => 'published',
    ]);

    $response = $this->getJson('/api/cars');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'make',
                    'model',
                    'year',
                    'daily_rate',
                    'location',
                    'is_available',
                    'owner',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(3);
});

test('can filter cars by location', function (): void {
    $owner = User::factory()->create();

    Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'location' => 'New York',
        'is_available' => true,
        'status' => 'published',
    ]);

    Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'location' => 'Los Angeles',
        'is_available' => true,
        'status' => 'published',
    ]);

    $response = $this->getJson('/api/cars?location=New York');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.location'))->toBe('New York');
});

test('can show single car', function (): void {
    $owner = User::factory()->create();
    $car = Vehicle::factory()->create([
        'owner_id' => $owner->id,
        'is_available' => true,
        'status' => 'published',
    ]);

    $response = $this->getJson("/api/cars/{$car->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'make',
                'model',
                'year',
                'daily_rate',
                'location',
                'description',
                'owner' => [
                    'id',
                    'name',
                    'email',
                ],
                'is_available',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $car->id,
                'make' => $car->make,
                'model' => $car->model,
            ],
        ]);
});

test('returns 404 for nonexistent car', function (): void {
    $response = $this->getJson('/api/cars/999');

    $response->assertNotFound();
});
