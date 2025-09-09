<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        $makes = ['Toyota', 'Honda', 'BMW', 'Mercedes-Benz', 'Audi', 'Ford', 'Chevrolet', 'Nissan', 'Hyundai', 'Kia'];
        $models = [
            'Toyota' => ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Prius'],
            'Honda' => ['Civic', 'Accord', 'CR-V', 'Pilot', 'Fit'],
            'BMW' => ['3 Series', '5 Series', 'X3', 'X5', '7 Series'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'GLC', 'GLE', 'S-Class'],
            'Audi' => ['A4', 'A6', 'Q5', 'Q7', 'A3'],
            'Ford' => ['F-150', 'Escape', 'Explorer', 'Mustang', 'Focus'],
            'Chevrolet' => ['Malibu', 'Equinox', 'Tahoe', 'Silverado', 'Cruze'],
            'Nissan' => ['Altima', 'Rogue', 'Sentra', 'Pathfinder', 'Versa'],
            'Hyundai' => ['Elantra', 'Tucson', 'Santa Fe', 'Sonata', 'Accent'],
            'Kia' => ['Optima', 'Sportage', 'Sorento', 'Rio', 'Soul'],
        ];

        $make = fake()->randomElement($makes);
        $model = fake()->randomElement($models[$make]);

        return [
            'owner_id' => User::factory()->owner(),
            'make' => $make,
            'model' => $model,
            'year' => fake()->numberBetween(2015, 2024),
            'plate_number' => fake()->bothify('???-####'),
            'vin' => fake()->bothify('1#?#?#?#?#?#?#?#?#?'),
            'fuel_type' => fake()->randomElement(['petrol', 'diesel', 'electric', 'hybrid']),
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            'daily_rate' => fake()->numberBetween(30, 150),
            'oil_type' => fake()->randomElement(['5W-30', '5W-40', '10W-30', '10W-40', null]),
            'last_oil_change' => fake()->optional(0.7)->dateTimeBetween('-1 year', '-1 month'),
            'policy' => fake()->optional(0.8)->paragraph(2),
            'status' => fake()->randomElement(['pending', 'approved', 'published']),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }
}
