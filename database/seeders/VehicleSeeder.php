<?php

namespace Database\Seeders;

use App\Models\Log;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::query()->where('role', 'owner')->get();
        $admin = User::query()->where('role', 'admin')->first();

        if ($owners->isEmpty()) {
            $this->command->warn('No owners found. Run UserSeeder first.');

            return;
        }

        // Create specific test vehicles with different statuses
        $testVehicles = [
            [
                'owner_email' => 'john.smith@example.com',
                'make' => 'Toyota',
                'model' => 'Camry',
                'year' => 2022,
                'color' => 'White',
                'plate_number' => 'ABC-1234',
                'daily_rate' => 45,
                'status' => 'published',
                'is_available' => true,
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'mileage' => 25000,
            ],
            [
                'owner_email' => 'sarah.johnson@example.com',
                'make' => 'BMW',
                'model' => '3 Series',
                'year' => 2023,
                'color' => 'Black',
                'plate_number' => 'XYZ-5678',
                'daily_rate' => 75,
                'status' => 'published',
                'is_available' => true,
                'fuel_type' => 'petrol',
                'transmission' => 'automatic',
                'seats' => 5,
                'mileage' => 15000,
            ],
            [
                'owner_email' => 'michael.brown@example.com',
                'make' => 'Honda',
                'model' => 'Civic',
                'year' => 2021,
                'color' => 'Red',
                'plate_number' => 'DEF-9012',
                'daily_rate' => 40,
                'status' => 'pending', // Pending approval
                'is_available' => false,
                'fuel_type' => 'petrol',
                'transmission' => 'manual',
                'seats' => 5,
                'mileage' => 35000,
            ],
            [
                'owner_email' => 'john.smith@example.com',
                'make' => 'Tesla',
                'model' => 'Model 3',
                'year' => 2023,
                'color' => 'Blue',
                'plate_number' => 'ELC-2024',
                'daily_rate' => 90,
                'status' => 'approved', // Approved but not published
                'is_available' => false,
                'fuel_type' => 'electric',
                'transmission' => 'automatic',
                'seats' => 5,
                'mileage' => 8000,
            ],
        ];

        foreach ($testVehicles as $vehicleData) {
            $owner = User::query()->where('email', $vehicleData['owner_email'])->first();
            if (! $owner) {
                continue;
            }

            // Remove owner_email from the data array since it's not a database column
            $cleanVehicleData = $vehicleData;
            unset($cleanVehicleData['owner_email']);

            $vehicle = Vehicle::query()->firstOrCreate(['plate_number' => $vehicleData['plate_number']], array_merge($cleanVehicleData, [
                'owner_id' => $owner->id,
                'vin' => fake()->bothify('1#?#?#?#?#?#?#?#?#?'),
                'description' => fake()->paragraph(3),
                'location' => fake()->city(),
                'insurance_expiry' => fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
            ]));

            // Add images for each vehicle
            VehicleImage::factory(random_int(3, 6))->create([
                'vehicle_id' => $vehicle->id,
            ]);

            // Set first image as primary
            $vehicle->images()->first()?->update(['is_primary' => true]);

            // Add creation log
            Log::factory()->created()->create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $vehicle->owner_id,
            ]);

            // Add approval log for non-pending vehicles
            if ($vehicle->status !== 'pending' && $admin) {
                Log::factory()->approved()->create([
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $admin->id,
                ]);
            }
        }

        // Create additional random vehicles for each owner
        foreach ($owners as $owner) {
            $vehicleCount = random_int(1, 3);
            $vehicles = Vehicle::factory($vehicleCount)->create(['owner_id' => $owner->id]);

            foreach ($vehicles as $vehicle) {
                // Add images
                VehicleImage::factory(random_int(2, 5))->create([
                    'vehicle_id' => $vehicle->id,
                ]);

                // Set primary image
                $vehicle->images()->first()?->update(['is_primary' => true]);

                // Add maintenance logs
                Log::factory(random_int(1, 3))->create([
                    'vehicle_id' => $vehicle->id,
                ]);

                // Add creation log
                Log::factory()->created()->create([
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $vehicle->owner_id,
                ]);

                // Add approval log for approved/published vehicles
                if (in_array($vehicle->status, ['approved', 'published']) && $admin) {
                    Log::factory()->approved()->create([
                        'vehicle_id' => $vehicle->id,
                        'user_id' => $admin->id,
                    ]);
                }
            }
        }
    }
}
