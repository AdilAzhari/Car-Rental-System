<?php

use App\Models\User;
use App\Models\Vehicle;
use Laravel\Dusk\Browser;

describe('Booking Flow Browser Tests', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->create();
        $this->renter = User::factory()->create([
            'email' => 'renter@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'daily_rate' => 100,
            'is_available' => true,
            'status' => 'published',
        ]);
    });

    it('can complete full booking flow', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/login')
                ->assertSee('Sign In')
                ->type('email', 'renter@test.com')
                ->type('password', 'password')
                ->press('Sign In')
                ->waitForText('Dashboard')
                ->visit('/cars')
                ->assertSee('Toyota Camry')
                ->click('@vehicle-card-'.$this->vehicle->id)
                ->waitForText('Vehicle Details')
                ->assertSee('$100')
                ->click('@reserve-button')
                ->waitForText('Booking Details')
                ->select('payment_method', 'cash')
                ->type('pickup_location', 'Downtown Office')
                ->type('special_requests', 'Need GPS')
                ->press('Complete Booking')
                ->waitForText('Booking Confirmed')
                ->assertSee('pending approval');
        });
    });

    it('validates booking form correctly', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->renter)
                ->visit("/cars/{$this->vehicle->id}")
                ->click('@reserve-button')
                ->waitForText('Booking Details')
                ->press('Complete Booking')
                ->waitForText('validation')
                ->assertSee('required'); // Should show validation errors
        });
    });

    it('displays image carousel correctly', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit("/cars/{$this->vehicle->id}")
                ->assertVisible('@image-carousel')
                ->assertVisible('@carousel-nav-next')
                ->assertVisible('@carousel-nav-prev')
                ->click('@carousel-nav-next')
                ->pause(500) // Wait for animation
                ->assertVisible('@image-carousel');
        });
    });

    it('shows booking confirmation for cash payments', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->renter)
                ->visit("/cars/{$this->vehicle->id}")
                ->click('@reserve-button')
                ->waitForText('Booking Details')
                ->select('payment_method', 'cash')
                ->type('start_date', now()->addDay()->toDateString())
                ->type('end_date', now()->addDays(3)->toDateString())
                ->press('Complete Booking')
                ->waitForText('Booking Created')
                ->assertSee('pending approval')
                ->assertSee('admin contact');
        });
    });

    it('handles payment processing for card payments', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->renter)
                ->visit("/cars/{$this->vehicle->id}")
                ->click('@reserve-button')
                ->waitForText('Booking Details')
                ->select('payment_method', 'visa')
                ->type('payment_method_id', 'pm_test_visa')
                ->type('start_date', now()->addDay()->toDateString())
                ->type('end_date', now()->addDays(2)->toDateString())
                ->press('Complete Booking')
                ->waitForText('Processing Payment')
                ->waitForText('Booking Confirmed', 10) // Wait up to 10 seconds
                ->assertSee('confirmed');
        });
    });
});

describe('Vehicle Search and Filtering', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->create();

        Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'seats' => 5,
            'daily_rate' => 100,
            'is_available' => true,
            'status' => 'published',
        ]);

        Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'transmission' => 'manual',
            'fuel_type' => 'hybrid',
            'seats' => 5,
            'daily_rate' => 80,
            'is_available' => true,
            'status' => 'published',
        ]);
    });

    it('can filter vehicles by transmission', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/cars')
                ->assertSee('Toyota Camry')
                ->assertSee('Honda Civic')
                ->select('@filter-transmission', 'automatic')
                ->press('@apply-filters')
                ->waitUntilMissingText('Honda Civic')
                ->assertSee('Toyota Camry')
                ->assertDontSee('Honda Civic');
        });
    });

    it('can filter vehicles by price range', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/cars')
                ->assertSee('Toyota Camry')
                ->assertSee('Honda Civic')
                ->type('@filter-price-min', '90')
                ->type('@filter-price-max', '110')
                ->press('@apply-filters')
                ->waitUntilMissingText('Honda Civic')
                ->assertSee('Toyota Camry')
                ->assertDontSee('Honda Civic');
        });
    });

    it('shows no results when filters match nothing', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/cars')
                ->select('@filter-transmission', 'manual')
                ->type('@filter-price-min', '200')
                ->press('@apply-filters')
                ->waitForText('No vehicles found')
                ->assertSee('No vehicles found');
        });
    });
});

describe('Enhanced UI Components', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->create();
        $this->vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'featured_image' => 'test-image.jpg',
            'gallery_images' => ['image1.jpg', 'image2.jpg', 'image3.jpg'],
        ]);
    });

    it('displays enhanced header with new branding', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->assertSee('CarZen')
                ->assertSee('Premium Rentals')
                ->assertVisible('@enhanced-logo')
                ->assertVisible('@nav-links');
        });
    });

    it('shows image carousel with navigation', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit("/cars/{$this->vehicle->id}")
                ->assertVisible('@image-carousel')
                ->assertVisible('@carousel-dots')
                ->click('@carousel-next')
                ->pause(1000)
                ->click('@carousel-fullscreen')
                ->waitForText('Close')
                ->assertVisible('@fullscreen-modal')
                ->keys('', '{escape}')
                ->waitUntilMissing('@fullscreen-modal');
        });
    });

    it('displays vehicle cards with enhanced design', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/cars')
                ->assertVisible('@vehicle-card')
                ->mouseover('@vehicle-card')
                ->assertVisible('@quick-view-button')
                ->assertVisible('@favorite-button');
        });
    });

    it('shows responsive mobile navigation', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->resize(375, 667) // iPhone dimensions
                ->visit('/')
                ->assertVisible('@mobile-menu-button')
                ->click('@mobile-menu-button')
                ->waitForText('Dashboard')
                ->assertVisible('@mobile-menu');
        });
    });
});

describe('Performance and Accessibility', function (): void {
    it('loads home page within performance budget', function (): void {
        $this->browse(function (Browser $browser): void {
            $startTime = microtime(true);

            $browser->visit('/');

            $loadTime = (microtime(true) - $startTime) * 1000;

            expect($loadTime)->toBeLessThan(3000); // Should load within 3 seconds
        });
    });

    it('has proper accessibility attributes', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->assertAttribute('@main-navigation', 'role', 'navigation')
                ->assertAttribute('@hero-heading', 'role', 'heading')
                ->assertPresent('[alt]'); // Images should have alt text
        });
    });

    it('handles errors gracefully', function (): void {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/cars/999999') // Non-existent vehicle
                ->assertSee('404')
                ->assertSee('Vehicle not found');
        });
    });
});
