<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserFavoritesController;
use App\Http\Controllers\ImageUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user());

// Public car routes - Higher rate limit for browsing
Route::middleware(['throttle:60,1'])->group(function (): void {
    Route::get('/cars', [CarController::class, 'index']);
    Route::get('/cars/{id}', [CarController::class, 'show']);
});

// Public webhook routes (no rate limit for webhooks)
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook']);

// Protected routes with different rate limits based on action sensitivity
Route::middleware(['auth:sanctum'])->group(function (): void {

    // High-frequency operations (favorites, status checks) - More permissive
    Route::middleware(['throttle:100,1'])->group(function (): void {
        Route::get('/favorites', [UserFavoritesController::class, 'index']);
        Route::get('/favorites/{vehicleId}', [UserFavoritesController::class, 'show']);
        Route::get('/payments/status/{bookingId}', [PaymentController::class, 'getPaymentStatus']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
    });

    // Medium-frequency operations (CRUD operations) - Moderate limits
    Route::middleware(['throttle:30,1'])->group(function (): void {
        Route::post('/favorites', [UserFavoritesController::class, 'store']);
        Route::post('/favorites/toggle', [UserFavoritesController::class, 'toggle']);
        Route::delete('/favorites/{vehicleId}', [UserFavoritesController::class, 'destroy']);

        // Image upload routes
        Route::post('/upload/profile-image', [ImageUploadController::class, 'uploadProfileImage']);
        Route::post('/upload/vehicle-image', [ImageUploadController::class, 'uploadVehicleImage']);
        Route::delete('/upload/image', [ImageUploadController::class, 'deleteImage']);
    });

    // Critical financial operations - Strict rate limiting
    Route::middleware(['throttle:10,1'])->group(function (): void {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::post('/payments/process', [PaymentController::class, 'processPayment']);
        Route::post('/payments/intent', [PaymentController::class, 'createPaymentIntent']);
        Route::post('/payments/{paymentId}/refund', [PaymentController::class, 'processRefund']);
    });

    // Admin-only operations - Very strict limits
    Route::middleware(['throttle:5,1', 'role:admin'])->group(function (): void {
        Route::post('/payments/{paymentId}/confirm-cash', [PaymentController::class, 'confirmCashPayment']);
    });
});
