<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Response;

class CarController extends Controller
{
    public function index(): Response
    {
        try {
            // Get featured/popular cars for homepage
            $cars = Vehicle::with(['images'])
                ->where('is_available', true)
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($car) {
                    return [
                        'id' => $car->id,
                        'make' => $car->make,
                        'model' => $car->model,
                        'year' => $car->year,
                        'daily_rate' => $car->daily_rate,
                        'seats' => $car->seats,
                        'transmission' => $car->transmission,
                        'featured_image' => $car->featured_image ?? ($car->images->first()->image_path ?? null),
                        'location' => $car->location,
                    ];
                });
        } catch (\Exception $e) {
            // If database tables don't exist yet, return empty array
            $cars = collect([]);
        }

        return Inertia::render('Home', [
            'cars' => $cars
        ]);
    }

    public function listing(): Response
    {
        // Get all cars with filters for the cars listing page
        $cars = Vehicle::with(['images'])
            ->where('is_available', true)
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return Inertia::render('Cars/Listing', [
            'cars' => $cars
        ]);
    }

    public function show(int $id): Response
    {
        $car = Vehicle::with(['images', 'owner'])
            ->where('id', $id)
            ->where('is_available', true)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Cars/Show', [
            'car' => $car
        ]);
    }
}
