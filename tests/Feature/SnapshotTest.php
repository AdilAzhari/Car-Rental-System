<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Snapshot Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('API Response Snapshots', function () {
        it('matches vehicle list API response structure', function () {
            Vehicle::factory(3)->create();

            $response = $this->actingAs($this->admin)
                ->getJson('/admin/vehicles')
                ->assertSuccessful();

            $responseData = $response->json();

            // Remove dynamic data for consistent snapshots
            $cleanedData = collect($responseData['data'] ?? [])->map(function ($vehicle) {
                return array_intersect_key($vehicle, array_flip([
                    'id', 'make', 'model', 'year', 'fuel_type', 'transmission', 'status'
                ]));
            });

            expect($cleanedData->toArray())->toMatchSnapshot();
        });

        it(/**
         * @throws JsonException
         */
        'matches booking creation response structure', function () {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);

            $bookingData = [
                'vehicle_id' => $vehicle->id,
                'start_date' => '2024-12-01',
                'end_date' => '2024-12-05',
                'total_amount' => 400.00,
                'notes' => 'Test booking'
            ];

            $response = $this->actingAs($this->renter)
                ->postJson('/admin/bookings', $bookingData);

            $responseData = $response->json();

            // Remove dynamic fields
            unset($responseData['data']['id'], $responseData['data']['created_at'], $responseData['data']['updated_at']);

            expect($responseData)->toMatchSnapshot();
        });

        it(/**
         * @throws JsonException
         */ 'matches user profile response structure', function () {
            $response = $this->actingAs($this->admin)
                ->getJson("/admin/users/{$this->admin->id}")
                ->assertSuccessful();

            $responseData = $response->json();

            // Remove dynamic/sensitive data
            unset(
                $responseData['data']['id'],
                $responseData['data']['email'],
                $responseData['data']['created_at'],
                $responseData['data']['updated_at'],
                $responseData['data']['email_verified_at']
            );

            expect($responseData)->toMatchSnapshot();
        });
    });

    describe('Database Query Snapshots', function () {
        it(/**
         * @throws JsonException
         */ 'matches complex booking query structure', function () {
            $vehicles = Vehicle::factory(5)->create(['owner_id' => $this->owner->id]);

            foreach ($vehicles as $vehicle) {
                Booking::factory(2)->create([
                    'vehicle_id' => $vehicle->id,
                    'renter_id' => $this->renter->id
                ]);
            }

            $query = Booking::with(['vehicle', 'renter'])
                ->where('renter_id', $this->renter->id)
                ->orderBy('created_at', 'desc')
                ->toSql();

            expect($query)->toMatchSnapshot();
        });

        it(/**
         * @throws JsonException
         */ 'matches vehicle search query with filters', function () {
            $query = Vehicle::query()
                ->where('status', 'published')
                ->where('fuel_type', 'petrol')
                ->whereBetween('daily_rate', [50, 200])
                ->with(['owner', 'vehicleImages'])
                ->orderBy('daily_rate')
                ->toSql();

            expect($query)->toMatchSnapshot();
        });
    });

    describe('HTML Response Snapshots', function () {
        it('matches dashboard HTML structure', function () {
            $response = $this->actingAs($this->admin)
                ->get('/admin')
                ->assertSuccessful();

            $html = $response->getContent();

            // Remove dynamic content like CSRF tokens and timestamps
            $cleanedHtml = preg_replace([
                '/name="_token" value="[^"]*"/',
                '/data-timestamp="[^"]*"/',
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
            ], [
                'name="_token" value="CSRF_TOKEN"',
                'data-timestamp="TIMESTAMP"',
                'DATE_TIME',
            ], $html);

            // Extract just the main content area for consistent snapshots
            $dom = new DOMDocument();
            @$dom->loadHTML($cleanedHtml);
            $xpath = new DOMXPath($dom);
            $mainContent = $xpath->query('//main[@class="dashboard-main"]');

            if ($mainContent->length > 0) {
                $content = $dom->saveHTML($mainContent->item(0));
                expect($content)->toMatchSnapshot();
            }
        });

        it('matches vehicle form HTML structure', function () {
            $response = $this->actingAs($this->admin)
                ->get('/admin/vehicles/create')
                ->assertSuccessful();

            $html = $response->getContent();

            // Extract form structure
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            $form = $xpath->query('//form[@class="vehicle-form"]');

            if ($form->length > 0) {
                $formHtml = $dom->saveHTML($form->item(0));

                // Clean dynamic values
                $cleanedForm = preg_replace([
                    '/name="_token" value="[^"]*"/',
                    '/id="[^"]*-\d+"/',
                ], [
                    'name="_token" value="CSRF_TOKEN"',
                    'id="DYNAMIC_ID"',
                ], $formHtml);

                expect($cleanedForm)->toMatchSnapshot();
            }
        });
    });

    describe('Configuration Snapshots', function () {
        it('matches database configuration structure', function () {
            $dbConfig = config('database.connections.mysql');

            // Remove sensitive data
            unset($dbConfig['password'], $dbConfig['username']);

            expect($dbConfig)->toMatchSnapshot();
        });

        it('matches mail configuration structure', function () {
            $mailConfig = config('mail');

            // Remove sensitive data
            if (isset($mailConfig['mailers']['smtp']['password'])) {
                unset($mailConfig['mailers']['smtp']['password']);
            }

            expect($mailConfig)->toMatchSnapshot();
        });

        it('matches filament configuration structure', function () {
            $filamentConfig = config('filament.default_filesystem_disk');

            expect($filamentConfig)->toMatchSnapshot();
        });
    });

    describe('Validation Error Snapshots', function () {
        it('matches vehicle validation error structure', function () {
            $response = $this->actingAs($this->admin)
                ->postJson('/admin/vehicles', [])
                ->assertStatus(422);

            $errors = $response->json('errors');
            expect($errors)->toMatchSnapshot();
        });

        it('matches booking validation error structure', function () {
            $response = $this->actingAs($this->renter)
                ->postJson('/admin/bookings', [
                    'start_date' => '2024-12-31',
                    'end_date' => '2024-12-01', // Invalid: end before start
                    'vehicle_id' => 999 // Non-existent
                ])
                ->assertStatus(422);

            $errors = $response->json('errors');
            expect($errors)->toMatchSnapshot();
        });

        it('matches user registration validation errors', function () {
            $response = $this->postJson('/register', [
                'name' => '',
                'email' => 'invalid-email',
                'password' => '123' // Too short
            ])
                ->assertStatus(422);

            $errors = $response->json('errors');
            expect($errors)->toMatchSnapshot();
        });
    });

    describe('Model Relationship Snapshots', function () {
        it('matches vehicle with relationships structure', function () {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);

            Booking::factory(2)->create(['vehicle_id' => $vehicle->id]);

            $vehicleData = $vehicle->load(['owner', 'bookings', 'reviews'])->toArray();

            // Remove dynamic data
            unset($vehicleData['created_at'], $vehicleData['updated_at'], $vehicleData['id']);
            unset($vehicleData['owner']['created_at'], $vehicleData['owner']['updated_at'], $vehicleData['owner']['id']);

            foreach ($vehicleData['bookings'] as &$booking) {
                unset($booking['created_at'], $booking['updated_at'], $booking['id']);
            }

            expect($vehicleData)->toMatchSnapshot();
        });

        it(/**
         * @throws JsonException
         */ 'matches user with full profile structure', function () {
            Vehicle::factory(2)->create(['owner_id' => $this->owner->id]);

            $userData = $this->owner->load(['vehicles', 'ownedBookings'])->toArray();

            // Remove dynamic/sensitive data
            unset($userData['created_at'], $userData['updated_at'], $userData['id'], $userData['email']);

            foreach ($userData['vehicles'] as &$vehicle) {
                unset($vehicle['created_at'], $vehicle['updated_at'], $vehicle['id']);
            }

            expect($userData)->toMatchSnapshot();
        });
    });
});
