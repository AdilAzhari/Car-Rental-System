<?php

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;

uses(RefreshDatabase::class);

describe('Complete Application Flow E2E Tests', function () {
    beforeEach(function () {
        $this->owner = User::factory()->owner()->create([
            'email' => 'owner@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->renter = User::factory()->renter()->create([
            'email' => 'renter@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->vehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'daily_rate' => 100,
            'is_available' => true,
            'status' => 'published',
            'featured_image' => 'vehicles/toyota-camry.jpg',
        ]);
    });

    it('completes full vehicle owner workflow', function () {
        $this->browse(function (Browser $browser) {
            // 1. Owner Registration and Login
            $browser->visit('/register')
                ->type('[data-testid="name"]', 'Vehicle Owner')
                ->type('[data-testid="email"]', 'newowner@example.com')
                ->type('[data-testid="password"]', 'password123')
                ->type('[data-testid="password_confirmation"]', 'password123')
                ->select('[data-testid="role"]', 'owner')
                ->press('[data-testid="register-btn"]')
                ->waitForText('Registration successful')

            // 2. Navigate to Dashboard
                ->visit('/admin')
                ->assertSee('Owner Dashboard')
                ->assertSee('My Vehicles')

            // 3. Add New Vehicle
                ->click('[data-testid="add-vehicle-btn"]')
                ->waitForText('Add New Vehicle')
                ->type('[data-testid="make"]', 'Honda')
                ->type('[data-testid="model"]', 'Civic')
                ->type('[data-testid="year"]', '2022')
                ->type('[data-testid="daily_rate"]', '85')
                ->select('[data-testid="transmission"]', 'manual')
                ->select('[data-testid="fuel_type"]', 'petrol')
                ->type('[data-testid="seats"]', '5')
                ->type('[data-testid="description"]', 'Reliable and fuel-efficient sedan')
                ->attach('[data-testid="featured_image"]', storage_path('app/public/test-images/honda-civic.jpg'))
                ->press('[data-testid="save-vehicle"]')
                ->waitForText('Vehicle added successfully')

            // 4. View Vehicle Listings
                ->visit('/admin/vehicles')
                ->assertSee('Honda Civic')
                ->assertSee('$85')
                ->assertSee('Available')

            // 5. Edit Vehicle Details
                ->click('[data-testid="edit-vehicle-1"]')
                ->waitForText('Edit Vehicle')
                ->clear('[data-testid="daily_rate"]')
                ->type('[data-testid="daily_rate"]', '90')
                ->press('[data-testid="update-vehicle"]')
                ->waitForText('Vehicle updated successfully')

            // 6. View Earnings Dashboard
                ->visit('/admin/earnings')
                ->assertSee('Earnings Overview')
                ->assertSee('Total Revenue')
                ->assertSee('Active Bookings');
        });
    });

    it('completes full vehicle rental workflow', function () {
        $this->browse(function (Browser $browser) {
            // 1. Renter Login
            $browser->visit('/login')
                ->type('[data-testid="email"]', 'renter@example.com')
                ->type('[data-testid="password"]', 'password123')
                ->press('[data-testid="login-btn"]')
                ->waitForText('Dashboard')

            // 2. Browse Available Vehicles
                ->visit('/cars')
                ->assertSee('Available Cars')
                ->assertSee('Toyota Camry')
                ->assertSee('$100')

            // 3. Apply Filters
                ->select('[data-testid="transmission-filter"]', 'automatic')
                ->type('[data-testid="price-max-input"]', '150')
                ->press('[data-testid="apply-filters-button"]')
                ->waitUntilMissing('[data-testid="loading-spinner"]')
                ->assertSee('Toyota Camry')

            // 4. View Vehicle Details
                ->click('[data-testid="view-details-button"]')
                ->waitForText('Vehicle Details')
                ->assertSee('Toyota Camry 2023')
                ->assertSee('$100 per day')
                ->assertVisible('[data-testid="image-carousel"]')

            // 5. Start Booking Process
                ->click('[data-testid="reserve-button"]')
                ->waitForText('Booking Details')
                ->type('[data-testid="start-date"]', now()->addDays(7)->toDateString())
                ->type('[data-testid="end-date"]', now()->addDays(10)->toDateString())
                ->select('[data-testid="payment-method"]', 'visa')
                ->type('[data-testid="payment-method-id"]', 'pm_test_visa')
                ->type('[data-testid="pickup-location"]', 'Main Office Downtown')
                ->type('[data-testid="special-requests"]', 'Need GPS and child seat')

            // 6. Review Booking Summary
                ->assertSee('3 days')
                ->assertSee('$300.00') // 3 days * $100
                ->assertSee('Insurance Fee')
                ->assertSee('Tax Amount')
                ->assertSee('Total Amount')

            // 7. Complete Booking
                ->press('[data-testid="complete-booking"]')
                ->waitForText('Processing Payment', 5)
                ->waitForText('Booking Confirmed', 15)
                ->assertSee('confirmed')
                ->assertSee('Booking Reference')

            // 8. View Booking in Dashboard
                ->visit('/admin/bookings')
                ->assertSee('My Bookings')
                ->assertSee('Toyota Camry')
                ->assertSee('confirmed')
                ->assertSee('Main Office Downtown');
        });
    });

    it('handles booking conflicts correctly', function () {
        $this->browse(function (Browser $browser1, Browser $browser2) {
            $renter1 = User::factory()->renter()->create([
                'email' => 'renter1@example.com',
                'password' => bcrypt('password123'),
            ]);

            $renter2 = User::factory()->renter()->create([
                'email' => 'renter2@example.com',
                'password' => bcrypt('password123'),
            ]);

            // Renter 1 starts booking process
            $browser1->loginAs($renter1)
                ->visit("/cars/{$this->vehicle->id}")
                ->click('[data-testid="reserve-button"]')
                ->waitForText('Booking Details')
                ->type('[data-testid="start-date"]', now()->addDays(5)->toDateString())
                ->type('[data-testid="end-date"]', now()->addDays(7)->toDateString())
                ->select('[data-testid="payment-method"]', 'cash');

            // Renter 2 tries to book same dates
            $browser2->loginAs($renter2)
                ->visit("/cars/{$this->vehicle->id}")
                ->click('[data-testid="reserve-button"]')
                ->waitForText('Booking Details')
                ->type('[data-testid="start-date"]', now()->addDays(6)->toDateString())
                ->type('[data-testid="end-date"]', now()->addDays(8)->toDateString())
                ->select('[data-testid="payment-method"]', 'cash');

            // Renter 1 completes booking first
            $browser1->press('[data-testid="complete-booking"]')
                ->waitForText('Booking Confirmed');

            // Renter 2 should get conflict error
            $browser2->press('[data-testid="complete-booking"]')
                ->waitForText('Vehicle not available')
                ->assertSee('dates conflict')
                ->assertSee('Please select different dates');
        });
    });

    it('completes admin management workflow', function () {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            // 1. Admin Login
            $browser->loginAs($admin)
                ->visit('/admin')
                ->assertSee('Admin Dashboard')
                ->assertSee('System Overview')

            // 2. Manage Users
                ->click('[data-testid="users-menu"]')
                ->waitForText('User Management')
                ->assertSee('All Users')
                ->assertSee($this->owner->name)
                ->assertSee($this->renter->name)

            // 3. View User Details
                ->click('[data-testid="view-user-'.$this->owner->id.'"]')
                ->waitForText('User Details')
                ->assertSee($this->owner->email)
                ->assertSee('owner')

            // 4. Manage Vehicles
                ->visit('/admin/vehicles')
                ->assertSee('All Vehicles')
                ->assertSee('Toyota Camry')
                ->assertSee('published')

            // 5. Approve/Reject Vehicle
                ->click('[data-testid="approve-vehicle-'.$this->vehicle->id.'"]')
                ->waitForText('Vehicle approved')
                ->assertSee('approved')

            // 6. View System Analytics
                ->visit('/admin/analytics')
                ->assertSee('System Analytics')
                ->assertSee('Total Users')
                ->assertSee('Total Vehicles')
                ->assertSee('Total Bookings')
                ->assertSee('Revenue Overview')

            // 7. Generate Reports
                ->click('[data-testid="generate-report"]')
                ->select('[data-testid="report-type"]', 'bookings')
                ->type('[data-testid="date-from"]', now()->subMonth()->toDateString())
                ->type('[data-testid="date-to"]', now()->toDateString())
                ->press('[data-testid="download-report"]')
                ->waitForText('Report generated')
                ->assertSee('Download ready');
        });
    });

    it('handles responsive design across devices', function () {
        $this->browse(function (Browser $browser) {
            // Desktop View
            $browser->resize(1920, 1080)
                ->visit('/cars')
                ->assertVisible('[data-testid="desktop-navigation"]')
                ->assertVisible('[data-testid="sidebar-filters"]')
                ->assertDontSee('[data-testid="mobile-menu-button"]');

            // Tablet View
            $browser->resize(768, 1024)
                ->refresh()
                ->assertVisible('[data-testid="tablet-navigation"]')
                ->assertVisible('[data-testid="filter-toggle"]');

            // Mobile View
            $browser->resize(375, 667)
                ->refresh()
                ->assertVisible('[data-testid="mobile-menu-button"]')
                ->assertDontSee('[data-testid="desktop-navigation"]')
                ->click('[data-testid="mobile-menu-button"]')
                ->waitForText('Menu')
                ->assertVisible('[data-testid="mobile-menu"]');
        });
    });

    it('handles search and filtering performance', function () {
        // Create more test data for performance testing
        Vehicle::factory()->count(50)->create([
            'owner_id' => $this->owner->id,
            'is_available' => true,
            'status' => 'published',
        ]);

        $this->browse(function (Browser $browser) {
            $startTime = microtime(true);

            $browser->visit('/cars')
                ->waitForText('Available Cars')
                ->type('[data-testid="search-input"]', 'Toyota')
                ->pause(500) // Wait for debounce
                ->waitUntilMissing('[data-testid="loading-spinner"]');

            $searchTime = (microtime(true) - $startTime) * 1000;

            expect($searchTime)->toBeLessThan(3000); // Should complete within 3 seconds

            $browser->assertSee('Toyota Camry')
                ->assertDontSee('Honda');
        });
    });

    it('maintains state during navigation', function () {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->renter)
                ->visit('/cars')
                ->type('[data-testid="search-input"]', 'Toyota')
                ->select('[data-testid="transmission-filter"]', 'automatic')
                ->press('[data-testid="apply-filters-button"]')
                ->waitUntilMissing('[data-testid="loading-spinner"]')

            // Navigate to details and back
                ->click('[data-testid="view-details-button"]')
                ->waitForText('Vehicle Details')
                ->back()
                ->waitForText('Available Cars')

            // Check if filters are maintained
                ->assertInputValue('[data-testid="search-input"]', 'Toyota')
                ->assertSelected('[data-testid="transmission-filter"]', 'automatic')
                ->assertSee('Toyota Camry');
        });
    });

    it('handles error scenarios gracefully', function () {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->renter)

            // Test network error handling
                ->visit('/cars/999999') // Non-existent vehicle
                ->assertSee('Vehicle not found')
                ->assertSee('Go back to search')

            // Test form validation
                ->visit("/cars/{$this->vehicle->id}")
                ->click('[data-testid="reserve-button"]')
                ->press('[data-testid="complete-booking"]') // Submit without required fields
                ->waitForText('validation')
                ->assertSee('Start date is required')
                ->assertSee('Payment method is required')

            // Test invalid date range
                ->type('[data-testid="start-date"]', now()->addDays(10)->toDateString())
                ->type('[data-testid="end-date"]', now()->addDays(5)->toDateString()) // End before start
                ->select('[data-testid="payment-method"]', 'cash')
                ->press('[data-testid="complete-booking"]')
                ->waitForText('Invalid date range')
                ->assertSee('End date must be after start date');
        });
    });

    it('supports multiple languages', function () {
        $this->browse(function (Browser $browser) {
            // Test Arabic language support
            $browser->visit('/cars')
                ->click('[data-testid="language-switcher"]')
                ->click('[data-testid="arabic-language"]')
                ->waitForText('السيارات المتاحة')
                ->assertSee('السيارات المتاحة') // "Available Cars" in Arabic
                ->assertSee('اليومي') // "Daily" in Arabic

            // Switch back to English
                ->click('[data-testid="language-switcher"]')
                ->click('[data-testid="english-language"]')
                ->waitForText('Available Cars')
                ->assertSee('Available Cars');
        });
    });
});
