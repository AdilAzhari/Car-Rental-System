<?php

namespace App\Actions\Vehicle;

use App\Http\Resources\CarResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchVehiclesAction
{
    public function execute(Request $request): AnonymousResourceCollection
    {
        $query = Vehicle::with(['owner', 'images', 'reviews'])
            ->availableForRent(
                $request->start_date,
                $request->end_date
            );

        // Apply filters using model scopes
        if ($request->filled('location')) {
            $query->nearby($request->location);
        }

        if ($request->filled(['price_min', 'price_max'])) {
            $query->priceRange($request->price_min, $request->price_max);
        }

        if ($request->filled('transmission')) {
            $query->where('transmission', $request->transmission);
        }

        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('seats')) {
            $query->where('seats', '>=', $request->seats);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        match ($sortBy) {
            'price_low' => $query->orderBy('daily_rate', 'asc'),
            'price_high' => $query->orderBy('daily_rate', 'desc'),
            'rating' => $query->orderByRaw('(SELECT AVG(rating) FROM reviews WHERE vehicle_id = car_rental_vehicles.id) DESC NULLS LAST'),
            'popular' => $query->popular(),
            default => $query->orderBy($sortBy, $sortOrder)
        };

        $perPage = min($request->get('per_page', 12), 50); // Max 50 per page
        $vehicles = $query->paginate($perPage);

        return CarResource::collection($vehicles);
    }
}
