<?php

namespace App\Http\Controllers\Api;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'car_id' => 'required|exists:car_rental_vehicles,id',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'payment_method' => 'required|in:stripe,visa,credit,tng,touch_n_go,cash,bank_transfer',
            'payment_method_id' => 'sometimes|string', // For Stripe payments
            'pickup_location' => 'sometimes|string|max:255',
            'special_requests' => 'sometimes|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $car = Vehicle::query()->findOrFail($validatedData['car_id']);

            // Check if car is available
            if (! $car->is_available || $car->status !== VehicleStatus::PUBLISHED->value) {
                throw ValidationException::withMessages([
                    'car_id' => 'This car is not available for booking.',
                ]);
            }

            $startDate = Carbon::parse($validatedData['start_date']);
            $endDate = Carbon::parse($validatedData['end_date']);

            // Check for overlapping bookings
            $overlappingBooking = Booking::query()->where('vehicle_id', $car->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($startDate, $endDate): void {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($overlapQuery) use ($startDate, $endDate): void {
                            $overlapQuery->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();

            if ($overlappingBooking) {
                throw ValidationException::withMessages([
                    'dates' => 'The car is not available for the selected dates.',
                ]);
            }

            // Calculate pricing
            $days = $startDate->diffInDays($endDate) + 1;
            $subtotal = $car->daily_rate * $days;

            // Add basic insurance (10% of subtotal)
            $insurance = $subtotal * 0.10;

            // Add taxes (8% of subtotal + insurance)
            $taxes = ($subtotal + $insurance) * 0.08;

            $totalAmount = $subtotal + $insurance + $taxes;

            // Create booking with pending status initially
            $booking = Booking::query()->create([
                'user_id' => $request->user()->id,
                'vehicle_id' => $car->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'daily_rate' => $car->daily_rate,
                'subtotal' => $subtotal,
                'insurance_fee' => $insurance,
                'tax_amount' => $taxes,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'pickup_location' => $validatedData['pickup_location'] ?? $car->location,
                'special_requests' => $validatedData['special_requests'] ?? null,
            ]);

            // Process payment using PaymentService
            $paymentResult = $this->paymentService->processPayment($booking, $validatedData);

            DB::commit();

            // Load relationships for response
            $booking->load(['vehicle.owner', 'vehicle.images', 'user', 'payments']);

            return response()->json([
                'success' => true,
                'booking' => new BookingResource($booking),
                'payment' => $paymentResult,
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Booking creation failed', [
                'user_id' => $request->user()->id,
                'car_id' => $validatedData['car_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking. Please try again.',
            ], 500);
        }
    }

    public function show(int $id): BookingResource
    {
        $booking = Booking::with(['vehicle.owner', 'vehicle.images', 'renter'])
            ->where('id', $id)
            ->where(function ($query): void {
                // User can only see their own bookings or bookings for their cars
                $query->where('renter_id', auth()->id())
                    ->orWhereHas('vehicle', function ($vehicleQuery): void {
                        $vehicleQuery->where('owner_id', auth()->id());
                    });
            })
            ->firstOrFail();

        return new BookingResource($booking);
    }
}
