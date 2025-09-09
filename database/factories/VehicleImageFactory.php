<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'image_path' => 'vehicles/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(4),
            'is_primary' => fake()->boolean(20),
        ];
    }

    public function primary(): static
    {
        return $this->state(['is_primary' => true]);
    }
}
