<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Performance Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('Database Performance', function () {
        it('handles large vehicle dataset efficiently', function () {
            // Create large dataset
            Vehicle::factory(1000)->create();
            
            $startTime = microtime(true);
            
            $this->actingAs($this->admin)
                ->get('/admin/vehicles')
                ->assertSuccessful();
            
            $executionTime = microtime(true) - $startTime;
            
            expect($executionTime)->toBeLessThan(2.0); // Should complete in under 2 seconds
        })->skip(function () {
            return !extension_loaded('xdebug');
        });

        it('handles concurrent booking creation', function () {
            $vehicles = Vehicle::factory(10)->create();
            
            $startTime = microtime(true);
            
            // Simulate concurrent booking attempts
            for ($i = 0; $i < 50; $i++) {
                Booking::factory()->create([
                    'vehicle_id' => $vehicles->random()->id,
                    'renter_id' => $this->renter->id
                ]);
            }
            
            $executionTime = microtime(true) - $startTime;
            
            expect($executionTime)->toBeLessThan(3.0); // Should complete in under 3 seconds
            expect(Booking::count())->toBe(50);
        });

        it('optimizes search queries', function () {
            Vehicle::factory(500)->create();
            
            $startTime = microtime(true);
            
            $this->actingAs($this->admin)
                ->get('/admin/vehicles?search=Toyota')
                ->assertSuccessful();
            
            $executionTime = microtime(true) - $startTime;
            
            expect($executionTime)->toBeLessThan(1.5); // Search should be fast
        });
    });

    describe('Memory Usage', function () {
        it('maintains reasonable memory usage during bulk operations', function () {
            $initialMemory = memory_get_usage();
            
            // Create and process large dataset
            Vehicle::factory(1000)->create();
            $vehicles = Vehicle::all();
            
            foreach ($vehicles as $vehicle) {
                $vehicle->load('owner', 'bookings');
            }
            
            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be reasonable (less than 50MB)
            expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);
        });

        it('handles pagination efficiently', function () {
            Vehicle::factory(10000)->create();
            
            $initialMemory = memory_get_usage();
            
            $this->actingAs($this->admin)
                ->get('/admin/vehicles?page=1')
                ->assertSuccessful();
            
            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Pagination should not load all records into memory
            expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024);
        });
    });

    describe('Stress Testing', function () {
        it('handles high concurrent user load', function () {
            $users = User::factory(100)->renter()->create();
            $vehicles = Vehicle::factory(50)->create();
            
            $startTime = microtime(true);
            
            // Simulate multiple concurrent requests
            foreach ($users as $user) {
                $this->actingAs($user)
                    ->get('/admin/vehicles')
                    ->assertSuccessful();
            }
            
            $executionTime = microtime(true) - $startTime;
            
            expect($executionTime)->toBeLessThan(10.0); // Should handle 100 requests in under 10 seconds
        });

        it('maintains performance under booking stress', function () {
            $renters = User::factory(50)->renter()->create();
            $vehicles = Vehicle::factory(20)->create();
            
            $startTime = microtime(true);
            
            // Create many bookings rapidly
            foreach ($renters as $renter) {
                foreach ($vehicles->take(5) as $vehicle) {
                    try {
                        Booking::factory()->create([
                            'vehicle_id' => $vehicle->id,
                            'renter_id' => $renter->id,
                            'start_date' => now()->addDays(rand(1, 30)),
                            'end_date' => now()->addDays(rand(31, 60))
                        ]);
                    } catch (\Exception $e) {
                        // Some bookings may conflict, which is expected
                    }
                }
            }
            
            $executionTime = microtime(true) - $startTime;
            
            expect($executionTime)->toBeLessThan(15.0);
            expect(Booking::count())->toBeGreaterThan(100);
        });
    });

    describe('API Performance', function () {
        it('API endpoints respond within acceptable time', function () {
            Vehicle::factory(100)->create();
            
            $routes = [
                '/admin/vehicles',
                '/admin/bookings',
                '/admin/users',
                '/admin/payments'
            ];
            
            foreach ($routes as $route) {
                $startTime = microtime(true);
                
                $this->actingAs($this->admin)
                    ->get($route)
                    ->assertSuccessful();
                
                $executionTime = microtime(true) - $startTime;
                
                expect($executionTime)->toBeLessThan(2.0); // Each route should respond in under 2 seconds
            }
        });

        it('handles bulk API operations efficiently', function () {
            $vehicles = Vehicle::factory(50)->create();
            
            $bulkData = [];
            foreach ($vehicles as $vehicle) {
                $bulkData[] = [
                    'id' => $vehicle->id,
                    'status' => 'published'
                ];
            }
            
            $startTime = microtime(true);
            
            $this->actingAs($this->admin)
                ->patch('/admin/vehicles/bulk-update', ['vehicles' => $bulkData]);
            
            $executionTime = microtime(true) - $startTime;
            
            expect($executionTime)->toBeLessThan(3.0); // Bulk operation should complete quickly
        });
    });

    describe('Database Query Performance', function () {
        it('uses efficient queries for complex operations', function () {
            Vehicle::factory(100)->create();
            User::factory(200)->renter()->create();
            Booking::factory(500)->create();
            
            \DB::enableQueryLog();
            
            $this->actingAs($this->admin)
                ->get('/admin/dashboard')
                ->assertSuccessful();
            
            $queries = \DB::getQueryLog();
            
            // Dashboard should not execute excessive queries (N+1 problem)
            expect(count($queries))->toBeLessThan(15);
            
            // No individual query should take too long
            foreach ($queries as $query) {
                expect($query['time'])->toBeLessThan(100); // 100ms max per query
            }
        });
    });
});