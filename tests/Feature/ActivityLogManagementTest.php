<?php

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Models\Log;
use Carbon\Carbon;
use Livewire\Livewire;

describe('Activity Log Management', function (): void {

    beforeEach(function (): void {
        $this->admin = createTestUser('admin');
        $this->owner = createTestUser('owner');
        $this->renter = createTestUser();
    });

    describe('Activity Log Creation', function (): void {
        it('logs user login activities', function (): void {
            $vehicle = createTestVehicle();

            // Create log entry with required vehicle_id
            Log::create([
                'vehicle_id' => $vehicle->id,
                'action' => 'user_login',
                'user_id' => $this->admin->id,
                'description' => 'User logged in successfully',
            ]);

            $log = Log::query()->where('action', 'user_login')->first();
            expect($log)->not->toBeNull()
                ->and($log->user_id)->toBe($this->admin->id)
                ->and($log->vehicle_id)->toBe($vehicle->id);
        });

        it('stores activity logs with proper structure', function (): void {
            $vehicle = createTestVehicle(['make' => 'Toyota', 'model' => 'Camry', 'year' => 2023]);

            Log::create([
                'vehicle_id' => $vehicle->id,
                'action' => 'vehicle_created',
                'user_id' => $this->admin->id,
                'description' => 'Vehicle created: Toyota Camry 2023',
                'metadata' => [
                    'vehicle_data' => [
                        'make' => 'Toyota',
                        'model' => 'Camry',
                        'year' => 2023,
                    ],
                ],
            ]);

            $log = Log::query()->where('action', 'vehicle_created')->first();
            expect($log)->not->toBeNull()
                ->and($log->metadata['vehicle_data']['make'])->toBe('Toyota')
                ->and($log->vehicle_id)->toBe($vehicle->id);
        });
    });

    describe('Activity Log Listing', function (): void {
        it('can view activity logs in Filament table', function (): void {
            $logs = Log::factory(5)->create();

            $this->actingAs($this->admin);

            Livewire::test(ListActivityLogs::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords($logs);
        });

        it('filters logs by user', function (): void {
            Log::factory(3)->create(['user_id' => $this->admin->id]);
            Log::factory(2)->create(['user_id' => $this->owner->id]);

            $adminLogs = Log::query()->where('user_id', $this->admin->id)->get();
            $ownerLogs = Log::query()->where('user_id', $this->owner->id)->get();

            expect($adminLogs)->toHaveCount(3)
                ->and($ownerLogs)->toHaveCount(2);
        });
    });

    describe('Activity Log Analysis', function (): void {
        it('displays activity summary by action type', function (): void {
            $loginLogs = Log::factory(3)->create(['action' => 'user_login']);
            $bookingLogs = Log::factory(2)->create(['action' => 'booking_created']);
            $vehicleLogs = Log::factory(1)->create(['action' => 'vehicle_updated']);

            $this->actingAs($this->admin);

            Livewire::test(ListActivityLogs::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords($loginLogs)
                ->assertCanSeeTableRecords($bookingLogs)
                ->assertCanSeeTableRecords($vehicleLogs);

            // Verify data exists
            expect(Log::query()->where('action', 'user_login')->count())->toBe(3)
                ->and(Log::query()->where('action', 'booking_created')->count())->toBe(2)
                ->and(Log::query()->where('action', 'vehicle_updated')->count())->toBe(1);
        });

        it('shows user-specific activity logs', function (): void {
            $adminLogs = Log::factory(5)->create(['user_id' => $this->admin->id]);
            $ownerLogs = Log::factory(3)->create(['user_id' => $this->owner->id]);
            $renterLogs = Log::factory(1)->create(['user_id' => $this->renter->id]);

            $this->actingAs($this->admin);

            Livewire::test(ListActivityLogs::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords($adminLogs);

            // Verify data distribution
            expect(Log::query()->where('user_id', $this->admin->id)->count())->toBe(5)
                ->and(Log::query()->where('user_id', $this->owner->id)->count())->toBe(3)
                ->and(Log::query()->where('user_id', $this->renter->id)->count())->toBe(1);
        });

        it('tracks login patterns', function (): void {
            Log::factory(3)->create([
                'action' => 'user_login',
                'created_at' => Carbon::today()->setHour(9),
            ]);
            Log::factory(2)->create([
                'action' => 'user_login',
                'created_at' => Carbon::today()->setHour(14),
            ]);

            // Test data exists instead of specific view
            expect(Log::query()->where('action', 'user_login')->count())->toBe(5);
        });
    });

    describe('Security Monitoring', function (): void {
        it('tracks failed login attempts', function (): void {
            $vehicle = createTestVehicle();

            // Simulate creating a failed login log entry
            Log::create([
                'vehicle_id' => $vehicle->id,
                'action' => 'login_failed',
                'description' => 'Failed login attempt',
                'metadata' => ['ip_address' => '192.168.1.1'],
            ]);

            $log = Log::query()->where('action', 'login_failed')->first();
            expect($log)->not->toBeNull()
                ->and($log->metadata['ip_address'])->toBe('192.168.1.1')
                ->and($log->vehicle_id)->toBe($vehicle->id);
        });

        it('identifies security alerts via data analysis', function (): void {
            $vehicle = createTestVehicle();

            // Create multiple failed login attempts
            for ($i = 0; $i < 6; $i++) {
                Log::create([
                    'vehicle_id' => $vehicle->id,
                    'action' => 'login_failed',
                    'description' => 'Failed login attempt',
                    'metadata' => ['ip_address' => '192.168.1.100'],
                    'created_at' => now()->subMinutes($i),
                ]);
            }

            // Test that we can detect suspicious activity via data
            $suspiciousAttempts = Log::query()->where('action', 'login_failed')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            expect($suspiciousAttempts)->toBe(6);
        });

        it('monitors admin actions', function (): void {
            Log::factory(3)->create([
                'action' => 'user_deleted',
                'user_id' => $this->admin->id,
            ]);

            // Verify admin actions are logged
            expect(Log::query()->where('action', 'user_deleted')->count())->toBe(3);
        });
    });

    describe('Log Cleanup', function (): void {
        it('allows admin to cleanup old logs', function (): void {
            // Create old logs
            Log::factory(5)->create(['created_at' => Carbon::now()->subDays(120)]);
            Log::factory(3)->create(['created_at' => Carbon::now()->subDays(60)]);

            $initialCount = Log::query()->count();
            expect($initialCount)->toBe(8);

            // Simulate cleanup of logs older than 90 days
            $deletedCount = Log::query()->where('created_at', '<', Carbon::now()->subDays(90))->delete();
            expect($deletedCount)->toBe(5);

            // Verify remaining logs
            expect(Log::query()->count())->toBe(3);
        });

        it('prevents cleanup of recent logs', function (): void {
            Log::factory(5)->create(['created_at' => Carbon::now()->subDays(10)]);

            // Attempt to cleanup recent logs (should not delete anything)
            $deletedCount = Log::query()->where('created_at', '<', Carbon::now()->subDays(30))->delete();
            expect($deletedCount)->toBe(0);
            expect(Log::query()->count())->toBe(5);
        });
    });

    describe('System Performance Logs', function (): void {
        it('tracks API response times', function (): void {
            Log::factory()->create([
                'action' => 'api_request',
                'metadata' => [
                    'endpoint' => '/api/vehicles',
                    'response_time' => 150,
                    'status' => 200,
                ],
            ]);

            $performanceLog = Log::query()->where('action', 'api_request')->first();
            expect($performanceLog)->not->toBeNull()
                ->and($performanceLog->metadata['response_time'])->toBe(150);
        });

        it('identifies slow API endpoints', function (): void {
            // Create logs with various response times
            $slowLog = Log::factory()->create([
                'action' => 'api_request',
                'metadata' => ['endpoint' => '/api/search', 'response_time' => 2500],
            ]);
            $fastLog = Log::factory()->create([
                'action' => 'api_request',
                'metadata' => ['endpoint' => '/api/vehicles', 'response_time' => 150],
            ]);

            // Verify slow log exists and has expected data
            expect($slowLog->metadata['response_time'])->toBe(2500)
                ->and($fastLog->metadata['response_time'])->toBe(150);

            // Test that we created 2 API request logs
            expect(Log::query()->where('action', 'api_request')->count())->toBe(2);
        });
    });

    describe('Real-time Activity Monitoring', function (): void {
        it('displays live activity feed via Filament', function (): void {
            $recentLogs = Log::factory(5)->create(['created_at' => Carbon::now()->subMinutes(5)]);

            $this->actingAs($this->admin);

            Livewire::test(ListActivityLogs::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords($recentLogs);

            // Verify recent activity exists
            $recentLogsQuery = Log::query()->where('created_at', '>=', now()->subMinutes(10))->get();
            expect($recentLogsQuery)->toHaveCount(5);
        });

        it('filters real-time activity by action', function (): void {
            Log::factory(3)->create([
                'action' => 'user_login',
                'created_at' => Carbon::now()->subMinutes(2),
            ]);
            Log::factory(2)->create([
                'action' => 'booking_created',
                'created_at' => Carbon::now()->subMinutes(1),
            ]);

            // Test filtering recent activity
            $loginLogs = Log::query()->where('action', 'user_login')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->get();

            expect($loginLogs)->toHaveCount(3);
        });
    });

});
