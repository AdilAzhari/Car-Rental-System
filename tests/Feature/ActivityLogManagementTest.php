<?php

use App\Models\Booking;
use App\Models\Log;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Random\RandomException;

uses(RefreshDatabase::class);

describe('Activity Log Management', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('Activity Log Listing', function (): void {
        it('allows admin to view all activity logs', function (): void {
            Log::factory(5)->create();

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs')
                ->assertSuccessful()
                ->assertSee('Activity Logs');
        });

        it('restricts owner access to activity logs', function (): void {
            $this->actingAs($this->owner)
                ->get('/admin/activity-logs')
                ->assertForbidden();
        });

        it('restricts renter access to activity logs', function (): void {
            $this->actingAs($this->renter)
                ->get('/admin/activity-logs')
                ->assertForbidden();
        });

        it('filters logs by user', function (): void {
            Log::factory(3)->create(['user_id' => $this->admin->id]);
            Log::factory(2)->create(['user_id' => $this->renter->id]);

            $this->actingAs($this->admin)
                ->get("/admin/activity-logs?user_id={$this->renter->id}")
                ->assertSuccessful();
        });

        it('filters logs by action type', function (): void {
            Log::factory()->create(['action' => 'user_login']);
            Log::factory()->create(['action' => 'booking_created']);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs?action=user_login')
                ->assertSuccessful();
        });

        it('filters logs by date range', function (): void {
            Log::factory()->create(['created_at' => Carbon::today()]);
            Log::factory()->create(['created_at' => Carbon::yesterday()]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs?from='.Carbon::today()->format('Y-m-d'))
                ->assertSuccessful();
        });
    });

    describe('Activity Log Creation', function (): void {
        it('logs user login activities', function (): void {
            $this->post('/login', [
                'email' => $this->renter->email,
                'password' => 'password',
            ]);

            $log = Log::query()->where('user_id', $this->renter->id)
                ->where('action', 'user_login')
                ->first();

            expect($log)->not->toBeNull()
                ->and($log->ip_address)->not->toBeNull();
        });

        it('logs vehicle creation activities', function (): void {
            $vehicleData = [
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2023,
                'plate_number' => 'ABC-1234',
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'daily_rate' => 100.00,
            ];

            $this->actingAs($this->owner)
                ->post('/admin/vehicles', $vehicleData);

            $log = Log::query()->where('user_id', $this->owner->id)
                ->where('action', 'vehicle_created')
                ->first();

            expect($log)->not->toBeNull()
                ->and($log->model_type)->toBe('Vehicle');
        });

        it('logs booking status changes', function (): void {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $vehicle->id,
            ]);

            $this->actingAs($this->admin)
                ->patch("/admin/bookings/$booking->id", [
                    'status' => 'confirmed',
                ]);

            $log = Log::query()->where('action', 'booking_status_changed')->first();
            expect($log)->not->toBeNull()
                ->and($log->model_id)->toBe($booking->id);
        });

        it('stores request details in log', function (): void {
            Log::factory()->create([
                'user_id' => $this->admin->id,
                'action' => 'vehicle_updated',
                'request_data' => json_encode(['daily_rate' => 120.00]),
                'user_agent' => 'Mozilla/5.0 Test Browser',
                'ip_address' => '192.168.1.1',
            ]);

            $log = Log::query()->where('action', 'vehicle_updated')->first();
            expect($log->request_data)->toContain('daily_rate')
                ->and($log->user_agent)->toBe('Mozilla/5.0 Test Browser')
                ->and($log->ip_address)->toBe('192.168.1.1');
        });
    });

    describe('Activity Log Analysis', function (): void {
        it('displays activity summary by action type', function (): void {
            Log::factory(3)->create(['action' => 'user_login']);
            Log::factory(2)->create(['action' => 'booking_created']);
            Log::factory(1)->create(['action' => 'vehicle_updated']);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/summary')
                ->assertSuccessful();
        });

        it('shows most active users', function (): void {
            Log::factory(5)->create(['user_id' => $this->admin->id]);
            Log::factory(3)->create(['user_id' => $this->owner->id]);
            Log::factory(1)->create(['user_id' => $this->renter->id]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/users')
                ->assertSuccessful();
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

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/login-patterns')
                ->assertSuccessful();
        });
    });

    describe('Security Monitoring', function (): void {
        it('tracks failed login attempts', function (): void {
            $this->post('/login', [
                'email' => $this->renter->email,
                'password' => 'wrongpassword',
            ]);

            $log = Log::query()->where('action', 'login_failed')->first();
            expect($log)->not->toBeNull()
                ->and($log->ip_address)->not->toBeNull();
        });

        it('logs suspicious activity patterns', function (): void {
            // Simulate multiple failed login attempts
            for ($i = 0; $i < 5; $i++) {
                Log::factory()->create([
                    'action' => 'login_failed',
                    'ip_address' => '192.168.1.100',
                    'created_at' => Carbon::now()->subMinutes($i),
                ]);
            }

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/security-alerts')
                ->assertSuccessful();
        });

        it('tracks admin actions', function (): void {
            Log::factory()->create([
                'user_id' => $this->admin->id,
                'action' => 'user_role_changed',
                'model_type' => 'User',
                'model_id' => $this->renter->id,
                'changes' => json_encode([
                    'role' => ['renter', 'owner'],
                ]),
            ]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/admin-actions')
                ->assertSuccessful();
        });
    });

    describe('Activity Log Export', function (): void {
        it('exports activity logs to CSV', function (): void {
            Log::factory(10)->create();

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/export?format=csv')
                ->assertSuccessful()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        });

        it('exports filtered logs', function (): void {
            Log::factory(5)->create(['user_id' => $this->admin->id]);
            Log::factory(3)->create(['user_id' => $this->renter->id]);

            $this->actingAs($this->admin)
                ->get("/admin/activity-logs/export?user_id={$this->renter->id}&format=csv")
                ->assertSuccessful();
        });

        it('exports logs for specific date range', function (): void {
            Log::factory(3)->create(['created_at' => Carbon::today()]);
            Log::factory(2)->create(['created_at' => Carbon::yesterday()]);

            $from = Carbon::today()->format('Y-m-d');
            $to = Carbon::today()->format('Y-m-d');

            $this->actingAs($this->admin)
                ->get("/admin/activity-logs/export?from=$from&to=$to&format=csv")
                ->assertSuccessful();
        });
    });

    describe('System Performance Logs', function (): void {
        it('logs slow query performance', function (): void {
            Log::factory()->create([
                'action' => 'slow_query',
                'request_data' => json_encode([
                    'query' => 'SELECT * FROM bookings WHERE...',
                    'execution_time' => 2.5,
                    'url' => '/admin/bookings',
                ]),
            ]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/performance')
                ->assertSuccessful();
        });

        it(/**
         * @throws RandomException
         */ 'tracks API response times', function (): void {
            Log::factory(5)->create([
                'action' => 'api_request',
                'request_data' => json_encode([
                    'endpoint' => '/api/vehicles',
                    'response_time' => random_int(100, 1000),
                    'status_code' => 200,
                ]),
            ]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/api-performance')
                ->assertSuccessful();
        });
    });

    describe('Log Cleanup', function (): void {
        it('allows admin to cleanup old logs', function (): void {
            Log::factory(10)->create(['created_at' => Carbon::now()->subDays(365)]);
            Log::factory(5)->create(['created_at' => Carbon::now()->subDays(30)]);

            $this->actingAs($this->admin)
                ->delete('/admin/activity-logs/cleanup?older_than=90')
                ->assertSuccessful();
        });

        it('prevents cleanup of recent logs', function (): void {
            Log::factory(5)->create(['created_at' => Carbon::now()->subDays(10)]);

            $this->actingAs($this->admin)
                ->delete('/admin/activity-logs/cleanup?older_than=5')
                ->assertSessionHasErrors();
        });
    });

    describe('Real-time Activity Monitoring', function (): void {
        it('displays live activity feed', function (): void {
            Log::factory(5)->create(['created_at' => Carbon::now()->subMinutes(5)]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/live')
                ->assertSuccessful();
        });

        it('filters live activity by action type', function (): void {
            Log::factory(3)->create([
                'action' => 'user_login',
                'created_at' => Carbon::now()->subMinutes(2),
            ]);

            $this->actingAs($this->admin)
                ->get('/admin/activity-logs/live?action=user_login')
                ->assertSuccessful();
        });
    });
});
