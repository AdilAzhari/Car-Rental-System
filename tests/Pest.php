<?php

/*
|--------------------------------------------------------------------------
| Test Case Configuration
|--------------------------------------------------------------------------
|
| Configure different test suites with appropriate base classes and traits
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit', 'Performance');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Parallel Testing Groups
|--------------------------------------------------------------------------
|
| Configure test groups for parallel execution optimization
|
*/

// Fast unit tests - ideal for parallel execution
pest()->group('parallel', 'fast')->in('Unit');

// API tests - can run in parallel with rate limiting considerations
pest()->group('parallel', 'api')->in('Feature/Api');

// Database-critical tests - should be isolated
pest()->group('database')->in('Feature/Auth', 'Feature/Payment');

// Browser tests - resource intensive, run separately
pest()->group('browser')->in('Browser');

/*
|--------------------------------------------------------------------------
| Performance Testing Helpers
|--------------------------------------------------------------------------
|
| Helper functions for measuring and asserting performance
|
*/

function measureExecutionTime(callable $callback): array
{
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    $result = $callback();

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    return [
        'result' => $result,
        'execution_time' => round(($endTime - $startTime) * 1000, 2), // ms
        'memory_used' => $endMemory - $startMemory,
        'peak_memory' => memory_get_peak_usage(),
    ];
}

function assertPerformance(callable $callback, float $maxExecutionMs = 1000, int $maxMemoryMb = 50): void
{
    $metrics = measureExecutionTime($callback);

    expect($metrics['execution_time'])
        ->toBeLessThan($maxExecutionMs,
            "Execution time {$metrics['execution_time']}ms exceeds limit of {$maxExecutionMs}ms");

    $memoryMb = $metrics['memory_used'] / 1024 / 1024;
    expect($memoryMb)
        ->toBeLessThan($maxMemoryMb,
            "Memory usage {$memoryMb}MB exceeds limit of {$maxMemoryMb}MB");
}

/*
|--------------------------------------------------------------------------
| Test Data Factories
|--------------------------------------------------------------------------
|
| Quick access to commonly used test data
|
*/

function createTestUser(string $role = 'renter'): \App\Models\User
{
    return \App\Models\User::factory()->create(['role' => $role]);
}

function createTestVehicle(array $attributes = []): \App\Models\Vehicle
{
    return \App\Models\Vehicle::factory()->create($attributes);
}

function createTestBooking(array $attributes = []): \App\Models\Booking
{
    return \App\Models\Booking::factory()->create($attributes);
}

/*
|--------------------------------------------------------------------------
| API Testing Helpers
|--------------------------------------------------------------------------
|
| Helpers for API endpoint testing with authentication
|
*/

function withAuthUser(string $role = 'renter'): \Illuminate\Contracts\Auth\Authenticatable
{
    $user = createTestUser($role);
    test()->actingAs($user, 'sanctum');

    return $user;
}

function expectApiSuccess(int $status = 200): void
{
    test()->assertStatus($status);
}

function expectRateLimited(): void
{
    test()->assertStatus(429)
        ->assertJson(['error' => 'Too many requests']);
}

function expectUnauthorized(): void
{
    test()->assertStatus(401);
}

function expectForbidden(): void
{
    test()->assertStatus(403);
}

/*
|--------------------------------------------------------------------------
| Enhanced Expectations
|--------------------------------------------------------------------------
|
| Custom expectations for this car rental application
|
*/

expect()->extend('toBeValidVehicle', fn () => $this->toHaveKeys(['id', 'make', 'model', 'year', 'daily_rate']));

expect()->extend('toBeValidBooking', fn () => $this->toHaveKeys(['id', 'start_date', 'end_date', 'status', 'total_amount']));

expect()->extend('toBeValidUser', fn () => $this->toHaveKeys(['id', 'name', 'email', 'role']));

expect()->extend('toBeWithinRange', fn (float $min, float $max) => $this->toBeGreaterThanOrEqual($min)->toBeLessThanOrEqual($max));

expect()->extend('toHaveValidTimestamps', fn () => $this->toHaveKeys(['created_at', 'updated_at']));

/*
|--------------------------------------------------------------------------
| Database Assertions
|--------------------------------------------------------------------------
|
| Helper functions for database-related assertions
|
*/

function expectDatabaseHasVehicle(array $attributes): void
{
    expect(\App\Models\Vehicle::where($attributes)->exists())->toBeTrue();
}

function expectDatabaseHasBooking(array $attributes): void
{
    expect(\App\Models\Booking::where($attributes)->exists())->toBeTrue();
}

function expectDatabaseCount(string $table, int $count): void
{
    expect(\DB::table($table)->count())->toBe($count);
}
