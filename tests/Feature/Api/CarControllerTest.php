<?php

use App\Enums\VehicleStatus;
use App\Enums\VehicleTransmission;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Repositories\VehicleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

describe('Vehicle Search and Filtering', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->create();

        // Create test vehicles with different characteristics
        $this->toyotaCamry = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'transmission' => VehicleTransmission::AUTOMATIC->value,
            'daily_rate' => 100,
            'seats' => 5,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        $this->hondaCivic = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'transmission' => VehicleTransmission::MANUAL->value,
            'daily_rate' => 80,
            'seats' => 5,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        $this->bmwX5 = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'BMW',
            'model' => 'X5',
            'transmission' => VehicleTransmission::AUTOMATIC->value,
            'daily_rate' => 200,
            'seats' => 7,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);
    });

    it('can filter by transmission type', function (): void {
        $response = $this->getJson('/api/cars?transmission=automatic');

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(2);
        expect(collect($data)->pluck('make'))->toContain('Toyota', 'BMW');
        expect(collect($data)->pluck('make'))->not->toContain('Honda');
    });

    it('can filter by price range', function (): void {
        $response = $this->getJson('/api/cars?price_min=90&price_max=150');

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(1);
        expect($data[0]['make'])->toBe('Toyota');
        expect($data[0]['daily_rate'])->toBe(100.0);
    });

    it('can filter by seat count', function (): void {
        $response = $this->getJson('/api/cars?seats=7');

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(1);
        expect($data[0]['make'])->toBe('BMW');
    });

    it('can combine multiple filters', function (): void {
        $response = $this->getJson('/api/cars?transmission=automatic&price_max=150');

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(1);
        expect($data[0]['make'])->toBe('Toyota');
    });

    it('can sort by price ascending', function (): void {
        $response = $this->getJson('/api/cars?sort_by=price&sort_direction=asc');

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(3);
        expect($data[0]['make'])->toBe('Honda'); // $80
        expect($data[1]['make'])->toBe('Toyota'); // $100
        expect($data[2]['make'])->toBe('BMW'); // $200
    });

    it('can sort by price descending', function (): void {
        $response = $this->getJson('/api/cars?sort_by=price&sort_direction=desc');

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(3);
        expect($data[0]['make'])->toBe('BMW'); // $200
        expect($data[1]['make'])->toBe('Toyota'); // $100
        expect($data[2]['make'])->toBe('Honda'); // $80
    });

    it('excludes vehicles with conflicting bookings', function (): void {
        $renter = User::factory()->create();

        // Create booking that conflicts with search dates
        Booking::factory()->create([
            'vehicle_id' => $this->toyotaCamry->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'status' => 'confirmed'
        ]);

        $response = $this->getJson('/api/cars?start_date=' . now()->addDays(6)->toDateString() . '&end_date=' . now()->addDays(8)->toDateString());

        $response->assertSuccessful();
        $data = $response->json('data');

        expect($data)->toHaveCount(2);
        expect(collect($data)->pluck('make'))->toContain('Honda', 'BMW');
        expect(collect($data)->pluck('make'))->not->toContain('Toyota');
    });

    it('handles pagination correctly', function (): void {
        // Create more vehicles for pagination testing
        Vehicle::factory()->count(15)->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        $response = $this->getJson('/api/cars?per_page=10');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);

        expect($response->json('data'))->toHaveCount(10);
        expect($response->json('meta.total'))->toBe(18); // 3 original + 15 new
    });
});

describe('Vehicle Details API', function (): void {
    it('includes featured image URL in response', function (): void {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $owner->id,
            'featured_image' => 'vehicles/test-car.jpg',
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        $response = $this->getJson("/api/cars/{$vehicle->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'featured_image_url'
                ]
            ]);

        expect($response->json('data.featured_image_url'))->toBe('/storage/vehicles/test-car.jpg');
    });

    it('includes vehicle images with primary flagging', function (): void {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $owner->id,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        VehicleImage::factory()->create([
            'vehicle_id' => $vehicle->id,
            'image_path' => 'vehicles/primary.jpg',
            'is_primary' => true
        ]);

        VehicleImage::factory()->create([
            'vehicle_id' => $vehicle->id,
            'image_path' => 'vehicles/secondary.jpg',
            'is_primary' => false
        ]);

        $response = $this->getJson("/api/cars/{$vehicle->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'images' => [
                        '*' => [
                            'id',
                            'image_path',
                            'is_primary'
                        ]
                    ]
                ]
            ]);

        $images = $response->json('data.images');
        expect($images)->toHaveCount(2);
        expect(collect($images)->where('is_primary', true))->toHaveCount(1);
    });

    it('includes owner information', function (): void {
        $owner = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $vehicle = Vehicle::factory()->create([
            'owner_id' => $owner->id,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        $response = $this->getJson("/api/cars/{$vehicle->id}");

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'owner' => [
                        'id' => $owner->id,
                        'name' => 'John Doe',
                        'email' => 'john@example.com'
                    ]
                ]
            ]);
    });

    it('returns 404 for unpublished vehicles', function (): void {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'owner_id' => $owner->id,
            'status' => VehicleStatus::DRAFT->value,
        ]);

        $response = $this->getJson("/api/cars/{$vehicle->id}");

        $response->assertNotFound();
    });
});

describe('Repository Integration', function (): void {
    it('uses VehicleRepository for search operations', function (): void {
        $repository = app(VehicleRepository::class);
        $owner = User::factory()->create();

        Vehicle::factory()->count(5)->create([
            'owner_id' => $owner->id,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        $request = new \Illuminate\Http\Request(['per_page' => 3]);
        $results = $repository->searchWithFilters($request);

        expect($results)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
        expect($results->items())->toHaveCount(3);
        expect($results->total())->toBe(5);
    });

    it('repository maintains performance under load', function (): void {
        $repository = app(VehicleRepository::class);
        $owner = User::factory()->create();

        Vehicle::factory()->count(50)->create([
            'owner_id' => $owner->id,
            'is_available' => true,
            'status' => VehicleStatus::PUBLISHED->value,
        ]);

        assertPerformance(function () use ($repository) {
            $request = new \Illuminate\Http\Request(['per_page' => 20]);
            return $repository->searchWithFilters($request);
        }, 1000, 20); // Max 1 second, 20MB memory
    });
});
