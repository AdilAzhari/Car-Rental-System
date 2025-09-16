<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateBookingAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ValidateVehicleAvailabilityAction $validateAvailability
    ) {}

    public function execute(array $validatedData): Booking
    {
        Log::info('🔧 CREATE BOOKING ACTION STARTED', [
            'user_id' => auth()->id(),
            'validated_data' => $validatedData,
            'timestamp' => now()
        ]);

        return DB::transaction(function () use ($validatedData) {
            Log::info('📊 DATABASE TRANSACTION STARTED', [
                'user_id' => auth()->id(),
                'car_id' => $validatedData['car_id']
            ]);

            try {
                // Validate vehicle availability
                Log::info('🚗 VALIDATING VEHICLE AVAILABILITY', [
                    'user_id' => auth()->id(),
                    'car_id' => $validatedData['car_id'],
                    'start_date' => $validatedData['start_date'],
                    'end_date' => $validatedData['end_date']
                ]);

                $vehicle = $this->validateAvailability->execute(
                    $validatedData['car_id'],
                    $validatedData['start_date'],
                    $validatedData['end_date']
                );

                Log::info('✅ VEHICLE VALIDATION PASSED', [
                    'user_id' => auth()->id(),
                    'vehicle_id' => $vehicle->id,
                    'vehicle_model' => $vehicle->model,
                    'daily_rate' => $vehicle->daily_rate,
                    'vehicle_status' => $vehicle->status,
                    'is_available' => $vehicle->is_available
                ]);

                // Calculate booking details
                $startDate = Carbon::parse($validatedData['start_date']);
                $endDate = Carbon::parse($validatedData['end_date']);
                $totalDays = $startDate->diffInDays($endDate) + 1;
                $totalAmount = $vehicle->daily_rate * $totalDays;

                Log::info('💰 BOOKING CALCULATIONS', [
                    'user_id' => auth()->id(),
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_days' => $totalDays,
                    'daily_rate' => $vehicle->daily_rate,
                    'total_amount' => $totalAmount
                ]);

                // Determine initial status based on payment method
                $paymentMethod = $validatedData['payment_method'] ?? 'cash';
                $initialStatus = match ($paymentMethod) {
                    'cash' => 'pending', // Cash payments need admin approval
                    'visa', 'credit' => 'pending', // Card payments process immediately
                    default => 'pending'
                };

                Log::info('💳 PAYMENT METHOD & STATUS', [
                    'user_id' => auth()->id(),
                    'payment_method' => $paymentMethod,
                    'initial_status' => $initialStatus
                ]);

                // Determine payment status based on payment method
                $paymentStatus = match ($paymentMethod) {
                    'cash' => 'unpaid', // Cash payments start as unpaid
                    'visa', 'credit' => 'unpaid', // Will be updated after payment processing
                    default => 'unpaid'
                };

                // Prepare booking data
                $bookingData = [
                    'renter_id' => auth()->id(),
                    'vehicle_id' => $vehicle->id,
                    'start_date' => $validatedData['start_date'],
                    'end_date' => $validatedData['end_date'],
                    'total_amount' => $totalAmount,
                    'status' => $initialStatus,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'pickup_location' => $validatedData['pickup_location'] ?? $vehicle->pickup_location ?? $vehicle->location ?? 'Main Office',
                    'dropoff_location' => $validatedData['dropoff_location'] ?? $validatedData['pickup_location'] ?? $vehicle->pickup_location ?? $vehicle->location ?? 'Main Office',
                    'special_requests' => $validatedData['special_requests'] ?? null,
                ];

                Log::info('📝 CREATING BOOKING WITH DATA', [
                    'user_id' => auth()->id(),
                    'booking_data' => $bookingData,
                    'auth_user' => auth()->user()->toArray()
                ]);

                // Create booking
                $booking = Booking::create($bookingData);

                Log::info('✨ BOOKING CREATED SUCCESSFULLY', [
                    'user_id' => auth()->id(),
                    'booking_id' => $booking->id,
                    'booking_exists_in_db' => Booking::where('id', $booking->id)->exists(),
                    'created_at' => $booking->created_at,
                    'booking_data' => $booking->toArray()
                ]);

                // Process payment for card payments
                if ($paymentMethod === 'visa' || $paymentMethod === 'credit') {
                    Log::info('💰 PROCESSING CARD PAYMENT', [
                        'user_id' => auth()->id(),
                        'booking_id' => $booking->id,
                        'payment_method' => $paymentMethod,
                        'payment_method_id' => $validatedData['payment_method_id'] ?? null
                    ]);

                    $paymentResult = $this->paymentService->processPayment(
                        $booking,
                        [
                            'payment_method' => $paymentMethod,
                            'payment_method_id' => $validatedData['payment_method_id'] ?? null
                        ]
                    );

                    Log::info('💳 PAYMENT PROCESSING RESULT', [
                        'user_id' => auth()->id(),
                        'booking_id' => $booking->id,
                        'payment_result' => $paymentResult
                    ]);

                    if ($paymentResult['success']) {
                        $booking->update([
                            'status' => 'confirmed',
                            'payment_status' => 'paid'
                        ]);
                        Log::info('✅ BOOKING STATUS UPDATED TO CONFIRMED', [
                            'user_id' => auth()->id(),
                            'booking_id' => $booking->id
                        ]);
                    } else {
                        $booking->update([
                            'status' => 'payment_failed',
                            'payment_status' => 'unpaid'
                        ]);
                        Log::error('❌ PAYMENT FAILED - STATUS UPDATED', [
                            'user_id' => auth()->id(),
                            'booking_id' => $booking->id,
                            'payment_message' => $paymentResult['message'] ?? 'Payment failed'
                        ]);
                        throw new \Exception($paymentResult['message'] ?? 'Payment failed');
                    }
                }

                // Load relationships and return
                $finalBooking = $booking->load(['vehicle', 'renter', 'payments']);

                Log::info('🎉 BOOKING ACTION COMPLETED SUCCESSFULLY', [
                    'user_id' => auth()->id(),
                    'booking_id' => $finalBooking->id,
                    'final_status' => $finalBooking->status,
                    'booking_persisted' => Booking::where('id', $finalBooking->id)->exists(),
                    'transaction_completed' => true
                ]);

                return $finalBooking;

            } catch (\Exception $e) {
                Log::error('💥 CREATE BOOKING ACTION EXCEPTION', [
                    'user_id' => auth()->id(),
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'stack_trace' => $e->getTraceAsString(),
                    'validated_data' => $validatedData
                ]);
                throw $e;
            }
        });
    }
}
