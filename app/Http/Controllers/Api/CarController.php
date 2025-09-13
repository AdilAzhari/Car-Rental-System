<?php

namespace App\Http\Controllers\Api;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Vehicle::with(['owner', 'images', 'reviews'])
            ->where('is_available', true)
            ->where('status', VehicleStatus::PUBLISHED->value);

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$request->location.'%');
        }

        // Filter by date availability
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Exclude cars that have bookings overlapping with requested dates
            $query->whereDoesntHave('bookings', function ($bookingQuery) use ($startDate, $endDate): void {
                $bookingQuery->where('status', '!=', 'cancelled')
                    ->where(function ($dateQuery) use ($startDate, $endDate): void {
                        $dateQuery->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($overlapQuery) use ($startDate, $endDate): void {
                                $overlapQuery->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
            });
        }

        // Filter by price range
        if ($request->filled('price_min')) {
            $query->where('daily_rate', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('daily_rate', '<=', $request->price_max);
        }

        // Filter by transmission
        if ($request->filled('transmission')) {
            $query->where('transmission', $request->transmission);
        }

        $cars = $query->paginate(12);

        return CarResource::collection($cars);
    }

    public function show(int $id): CarResource
    {
        $car = Vehicle::with([
            'owner',
            'images',
            'reviews.renter',
            'bookings' => function ($query): void {
                $query->where('status', '!=', 'cancelled')
                    ->where('end_date', '>=', now());
            },
        ])->findOrFail($id);

        return new CarResource($car);
    }
}
