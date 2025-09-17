<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\CarController;
use App\Http\Controllers\Web\ReservationController;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Simple status route for testing
Route::get('/status', function() {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'database' => 'Connected',
        'app_name' => config('app.name'),
        'environment' => config('app.env'),
    ]);
});

// Test route for debugging
Route::get('/test-inertia', function() {
    return \Inertia\Inertia::render('Test', ['message' => 'Hello from Inertia!']);
});

// Homepage - shows featured cars
Route::get('/', [CarController::class, 'index']);

// Car routes
Route::get('/cars', [CarController::class, 'listing']);
Route::get('/cars/{id}', [CarController::class, 'show']);

// Reservation routes
Route::get('/reservations/create', [ReservationController::class, 'create']);
Route::get('/cars/{id}/reserve', [ReservationController::class, 'reserve'])->name('cars.reserve');

// Payment return route (can be accessed without auth for payment callbacks)
Route::get('/booking/payment/return/{booking}', [BookingController::class, 'paymentReturn'])->name('booking.payment.return');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/my-bookings', [BookingController::class, 'index']);
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

require __DIR__.'/auth.php';

// Debug route for Filament authentication
Route::get('/debug-filament-auth', function () {
    $user = Auth::user();

    $response = [
        'request_url' => request()->fullUrl(),
        'auth_check' => Auth::check(),
        'user' => null,
        'panel_access' => false,
        'session_data' => [
            'session_id' => session()->getId(),
            'session_token' => session()->token(),
            'auth_guard' => config('auth.defaults.guard'),
        ],
        'middlewares' => [],
        'filament_panel_exists' => false,
    ];

    if ($user) {
        $response['user'] = [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'is_verified' => $user->is_verified,
            'table' => $user->getTable(),
            'auth_identifier' => $user->getAuthIdentifier(),
        ];

        // Test canAccessPanel method
        try {
            $panel = Filament::getPanel('admin');
            $response['filament_panel_exists'] = true;
            $response['panel_access'] = $user->canAccessPanel($panel);
            $response['panel_id'] = $panel->getId();
            $response['panel_path'] = $panel->getPath();
        } catch (Exception $e) {
            $response['panel_error'] = $e->getMessage();
        }
    }

    return response()->json($response);
})->name('debug.filament.auth');

// Protected debug route to test auth after login
Route::middleware('auth')->get('/debug-filament-auth-protected', function () {
    $user = Auth::user();

    $response = [
        'timestamp' => now()->toDateTimeString(),
        'request_url' => request()->fullUrl(),
        'auth_check' => Auth::check(),
        'auth_guard' => Auth::getDefaultDriver(),
        'user_data' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'role_value' => $user->role->value,
            'status' => $user->status,
            'status_value' => $user->status->value,
            'is_verified' => $user->is_verified,
            'table' => $user->getTable(),
            'auth_identifier' => $user->getAuthIdentifier(),
        ],
        'role_comparison' => [
            'user_role_class' => get_class($user->role),
            'admin_role_class' => get_class(\App\Enums\UserRole::ADMIN),
            'user_role_value' => $user->role->value,
            'admin_role_value' => \App\Enums\UserRole::ADMIN->value,
            'strict_equality' => $user->role === \App\Enums\UserRole::ADMIN,
            'value_equality' => $user->role->value === \App\Enums\UserRole::ADMIN->value,
        ],
        'panel_test' => null,
    ];

    // Test Filament panel access
    try {
        $panel = Filament::getPanel('admin');
        $canAccess = $user->canAccessPanel($panel);

        $response['panel_test'] = [
            'panel_exists' => true,
            'panel_id' => $panel->getId(),
            'panel_path' => $panel->getPath(),
            'can_access' => $canAccess,
            'auth_guard' => $panel->getAuthGuard() ?? 'default',
        ];
    } catch (Exception $e) {
        $response['panel_test'] = [
            'panel_exists' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ];
    }

    return response()->json($response, 200, [], JSON_PRETTY_PRINT);
})->name('debug.filament.auth.protected');
