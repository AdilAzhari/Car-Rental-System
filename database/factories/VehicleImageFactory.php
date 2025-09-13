<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleImageFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['economy', 'compact', 'midsize', 'luxury', 'suv', 'sports', 'convertible', 'pickup', 'minivan', 'electric'];
        $category = fake()->randomElement($categories);

        $availableImages = [
            // Economy
            'economy-toyota-corolla.jpg', 'economy-nissan-altima.jpg', 'economy-hyundai-elantra.jpg',
            'economy-kia-forte.jpg', 'economy-chevrolet-malibu.jpg', 'economy-ford-focus.jpg',

            // Compact
            'compact-honda-civic.jpg', 'compact-volkswagen-golf.jpg', 'compact-mazda-3.jpg',
            'compact-subaru-impreza.jpg', 'compact-mini-cooper.jpg', 'compact-toyota-yaris.jpg',

            // Midsize
            'midsize-toyota-camry.jpg', 'midsize-honda-accord.jpg', 'midsize-hyundai-sonata.jpg',
            'midsize-nissan-maxima.jpg', 'midsize-mazda-6.jpg', 'midsize-kia-optima.jpg',

            // Luxury
            'luxury-bmw-3series.jpg', 'luxury-mercedes-cclass.jpg', 'luxury-audi-a4.jpg',
            'luxury-lexus-es.jpg', 'luxury-jaguar-xf.jpg', 'luxury-cadillac-ct5.jpg',
            'luxury-genesis-g80.jpg', 'luxury-volvo-s90.jpg', 'luxury-bmw-5series.jpg',
            'luxury-mercedes-eclass.jpg',

            // SUV
            'suv-toyota-rav4.jpg', 'suv-honda-crv.jpg', 'suv-ford-explorer.jpg',
            'suv-chevrolet-tahoe.jpg', 'suv-jeep-grand-cherokee.jpg', 'suv-bmw-x3.jpg',
            'suv-mercedes-gle.jpg', 'suv-audi-q5.jpg', 'suv-lexus-rx.jpg', 'suv-mazda-cx5.jpg',

            // Sports
            'sports-porsche-911.jpg', 'sports-bmw-m4.jpg', 'sports-ferrari-488.jpg',
            'sports-lamborghini-huracan.jpg', 'sports-ford-mustang.jpg', 'sports-chevrolet-corvette.jpg',
            'sports-audi-r8.jpg', 'sports-mclaren-720s.jpg', 'sports-nissan-gtr.jpg',

            // Convertible
            'convertible-bmw-z4.jpg', 'convertible-mercedes-slc.jpg', 'convertible-audi-tt.jpg',
            'convertible-porsche-boxster.jpg', 'convertible-mazda-mx5.jpg', 'convertible-ford-mustang.jpg',

            // Pickup
            'pickup-ford-f150.jpg', 'pickup-chevrolet-silverado.jpg', 'pickup-toyota-tacoma.jpg',
            'pickup-ram-1500.jpg', 'pickup-nissan-titan.jpg', 'pickup-gmc-sierra.jpg',

            // Minivan
            'minivan-honda-odyssey.jpg', 'minivan-toyota-sienna.jpg', 'minivan-chrysler-pacifica.jpg',

            // Electric
            'electric-tesla-model3.jpg', 'electric-tesla-models.jpg', 'electric-tesla-modely.jpg',
            'electric-nissan-leaf.jpg', 'electric-bmw-i4.jpg', 'electric-audi-etron.jpg',
        ];

        // Add placeholder images for variety
        for ($i = 1; $i <= 10; $i++) {
            foreach ($categories as $cat) {
                $availableImages[] = "{$cat}-placeholder-{$i}.jpg";
            }
        }

        $imagePath = 'vehicles/'.fake()->randomElement($availableImages);

        return [
            'vehicle_id' => Vehicle::factory(),
            'image_path' => $imagePath,
            'alt_text' => fake()->randomElement([
                'Front view of the vehicle',
                'Side view of the car',
                'Interior dashboard view',
                'Rear view of the automobile',
                'Engine compartment view',
                'Driver seat interior',
                'Wheel and tire close-up',
                'Trunk/cargo space',
                'Steering wheel and controls',
                'Passenger compartment',
                'Vehicle profile shot',
                'Headlight detail',
                'Exterior styling detail',
                'Dashboard instruments',
                'Seat upholstery detail',
            ]),
            'is_primary' => fake()->boolean(15), // 15% chance of being primary
        ];
    }

    public function primary(): static
    {
        return $this->state(['is_primary' => true]);
    }
}
