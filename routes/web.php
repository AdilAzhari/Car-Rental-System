<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\CarController;
use App\Http\Controllers\Web\ReservationController;
use Illuminate\Support\Facades\Route;

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
