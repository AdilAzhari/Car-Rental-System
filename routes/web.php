<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Web\CarController;
use App\Http\Controllers\Web\ReservationController;
use Illuminate\Support\Facades\Route;

// Car listing and details routes
Route::get('/', [CarController::class, 'index']);
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{id}', [CarController::class, 'show']);

// Reservation routes
Route::get('/reservations/create', [ReservationController::class, 'create']);

// Dashboard routes (will need auth middleware later)
Route::get('/dashboard', [DashboardController::class, 'index']);

require __DIR__.'/auth.php';
