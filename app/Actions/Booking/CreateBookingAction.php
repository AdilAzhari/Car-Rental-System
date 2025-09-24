<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class CreateBookingAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ValidateVehicleAvailabilityAction $validateVehicleAvailabilityAction,
        private readonly TransactionService $transactionService
    ) {}

    public function execute(array $validatedData): Booking
    {
        $createBookingDTO = \App\DTOs\CreateBookingDTO::fromArray($validatedData);

        return $this->executeWithDTO($createBookingDTO);
    }

    public function executeWithDTO(\App\DTOs\CreateBookingDTO $createBookingDTO): Booking
    {
        Log::info('🔧 CREATE BOOKING ACTION STARTED', [
            'user_id' => auth()->id(),
            'booking_data' => $createBookingDTO->toArray(),
            'timestamp' => now(),
        ]);

        return $this->transactionService->executeWithRetry(function () use ($createBookingDTO) {
            Log::info('📊 DATABASE TRANSACTION STARTED', [
                'user_id' => auth()->id(),
                'car_id' => $createBookingDTO->carId,
            ]);

            try {
                // Validate vehicle availability
                Log::info('🚗 VALIDATING VEHICLE AVAILABILITY', [
                    'user_id' => auth()->id(),
                    'car_id' => $createBookingDTO->carId,
                    'start_date' => $createBookingDTO->startDate->toDateString(),
                    'end_date' => $createBookingDTO->endDate->toDateString(),
                ]);

                $vehicle = $this->validateVehicleAvailabilityAction->execute(
                    $createBookingDTO->carId,
                    $createBookingDTO->startDate->toDateString(),
                    $createBookingDTO->endDate->toDateString()
                );

                Log::info('✅ VEHICLE VALIDATION PASSED', [
                    'user_id' => auth()->id(),
                    'vehicle_id' => $vehicle->id,
                    'vehicle_model' => $vehicle->model,
                    'daily_rate' => $vehicle->daily_rate,
                    'vehicle_status' => $vehicle->status,
                    'is_available' => $vehicle->is_available,
                ]);

                // Calculate booking details using DTO
                $calculation = \App\DTOs\BookingCalculationDTO::calculate(
                    $vehicle->daily_rate,
                    $createBookingDTO->durationDays
                );

                Log::info('💰 BOOKING CALCULATIONS', [
                    'user_id' => auth()->id(),
                    'start_date' => $createBookingDTO->startDate->toDateString(),
                    'end_date' => $createBookingDTO->endDate->toDateString(),
                    'calculation' => $calculation->toArray(),
                ]);

                // Determine initial status based on payment method
                $initialStatus = match ($createBookingDTO->paymentMethod) {
                    'cash' => 'pending', // Cash payments need admin approval
                    'visa', 'credit' => 'pending', // Card payments process immediately
                    default => 'pending'
                };

                Log::info('💳 PAYMENT METHOD & STATUS', [
                    'user_id' => auth()->id(),
                    'payment_method' => $createBookingDTO->paymentMethod,
                    'initial_status' => $initialStatus,
                ]);

                // Determine payment status based on payment method
                $paymentStatus = match ($createBookingDTO->paymentMethod) {
                    'cash' => 'unpaid', // Cash payments start as unpaid
                    'visa', 'credit' => 'unpaid', // Will be updated after payment processing
                    default => 'unpaid'
                };

                // Prepare booking data
                $bookingData = [
                    'renter_id' => $createBookingDTO->renterId,
                    'vehicle_id' => $vehicle->id,
                    'start_date' => $createBookingDTO->startDate->toDateString(),
                    'end_date' => $createBookingDTO->endDate->toDateString(),
                    'total_amount' => $calculation->totalAmount,
                    'status' => $initialStatus,
                    'payment_method' => $createBookingDTO->paymentMethod,
                    'payment_status' => $paymentStatus,
                    'pickup_location' => $createBookingDTO->pickupLocation ?? $vehicle->pickup_location ?? $vehicle->location ?? 'Main Office',
                    'dropoff_location' => $createBookingDTO->dropoffLocation ?? $createBookingDTO->pickupLocation ?? $vehicle->pickup_location ?? $vehicle->location ?? 'Main Office',
                    'special_requests' => $createBookingDTO->specialRequests,
                ];

                Log::info('📝 CREATING BOOKING WITH DATA', [
                    'user_id' => auth()->id(),
                    'booking_data' => $bookingData,
                    'auth_user' => auth()->user()->toArray(),
                ]);

                // Create booking
                $booking = Booking::create($bookingData);

                Log::info('✨ BOOKING CREATED SUCCESSFULLY', [
                    'user_id' => auth()->id(),
                    'booking_id' => $booking->id,
                    'booking_exists_in_db' => Booking::where('id', $booking->id)->exists(),
                    'created_at' => $booking->created_at,
                    'booking_data' => $booking->toArray(),
                ]);

                // Process payment for card payments
                if ($createBookingDTO->paymentMethod === 'visa' || $createBookingDTO->paymentMethod === 'credit') {
                    Log::info('💰 PROCESSING CARD PAYMENT', [
                        'user_id' => auth()->id(),
                        'booking_id' => $booking->id,
                        'payment_method' => $createBookingDTO->paymentMethod,
                        'payment_method_id' => $createBookingDTO->paymentMethodId,
                    ]);

                    $paymentResult = $this->paymentService->processPayment(
                        $booking,
                        [
                            'payment_method' => $createBookingDTO->paymentMethod,
                            'payment_method_id' => $createBookingDTO->paymentMethodId,
                        ]
                    );

                    Log::info('💳 PAYMENT PROCESSING RESULT', [
                        'user_id' => auth()->id(),
                        'booking_id' => $booking->id,
                        'payment_result' => $paymentResult,
                    ]);

                    if ($paymentResult['success']) {
                        $booking->update([
                            'status' => 'confirmed',
                            'payment_status' => 'paid',
                        ]);
                        Log::info('✅ BOOKING STATUS UPDATED TO CONFIRMED', [
                            'user_id' => auth()->id(),
                            'booking_id' => $booking->id,
                        ]);
                    } else {
                        $booking->update([
                            'status' => 'payment_failed',
                            'payment_status' => 'unpaid',
                        ]);
                        Log::error('❌ PAYMENT FAILED - STATUS UPDATED', [
                            'user_id' => auth()->id(),
                            'booking_id' => $booking->id,
                            'payment_message' => $paymentResult['message'] ?? 'Payment failed',
                        ]);
                        throw new \Exception($paymentResult['message'] ?? 'Payment failed');
                    }
                }

                // Load relationships and return
                $finalBooking = $booking->load(['vehicle.owner', 'renter', 'payments']);

                // Dispatch booking created event
                \App\Events\BookingCreated::dispatch($finalBooking);

                Log::info('🎉 BOOKING ACTION COMPLETED SUCCESSFULLY', [
                    'user_id' => auth()->id(),
                    'booking_id' => $finalBooking->id,
                    'final_status' => $finalBooking->status,
                    'booking_persisted' => Booking::where('id', $finalBooking->id)->exists(),
                    'transaction_completed' => true,
                    'event_dispatched' => true,
                ]);

                return $finalBooking;

            } catch (\Exception $e) {
                Log::error('💥 CREATE BOOKING ACTION EXCEPTION', [
                    'user_id' => auth()->id(),
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'stack_trace' => $e->getTraceAsString(),
                    'booking_data' => $createBookingDTO->toArray(),
                ]);
                throw $e;
            }
        });
    }
}
