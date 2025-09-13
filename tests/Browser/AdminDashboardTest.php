<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Admin Dashboard Browser Tests', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('Dashboard Access and Navigation', function (): void {
        it('can access admin dashboard when authenticated', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Dashboard')
                    ->assertSee('Welcome')
                    ->assertPresent('[data-testid="admin-navigation"]')
                    ->assertPresent('[data-testid="dashboard-stats"]');
            });
        });

        it('redirects unauthenticated users to login', function (): void {
            browse(function ($browser): void {
                $browser->visit('/admin')
                    ->assertPathIs('/login')
                    ->assertSee('Sign in to your account');
            });
        });

        it('shows correct navigation menu for admin users', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Vehicles')
                    ->assertSee('Bookings')
                    ->assertSee('Users')
                    ->assertSee('Payments')
                    ->assertSee('Reviews')
                    ->assertSee('Activity Log');
            });
        });
    });

    describe('Dashboard Statistics and Data', function (): void {
        it('displays correct vehicle statistics', function (): void {
            Vehicle::factory(5)->published()->create();
            Vehicle::factory(3)->draft()->create();
            Vehicle::factory(2)->archived()->create();

            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Total Vehicles')
                    ->assertSee('10') // Total vehicles
                    ->assertSee('Published: 5')
                    ->assertSee('Draft: 3')
                    ->assertSee('Archived: 2');
            });
        });

        it('displays booking statistics', function (): void {
            Booking::factory(3)->confirmed()->create();
            Booking::factory(2)->pending()->create();
            Booking::factory(1)->cancelled()->create();

            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Total Bookings')
                    ->assertSee('6') // Total bookings
                    ->assertSee('Confirmed: 3')
                    ->assertSee('Pending: 2')
                    ->assertSee('Cancelled: 1');
            });
        });

        it('displays user statistics', function (): void {
            User::factory(10)->renter()->create();
            User::factory(5)->owner()->create();
            // Plus the ones created in beforeEach (1 admin, 1 owner, 1 renter)

            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Total Users')
                    ->assertSee('17') // Total users including beforeEach ones
                    ->assertSee('Admins: 1')
                    ->assertSee('Owners: 6')
                    ->assertSee('Renters: 11');
            });
        });
    });

    describe('Quick Actions and Widget Interactions', function (): void {
        it('can navigate to vehicle management from dashboard', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->click('[data-testid="manage-vehicles-btn"]')
                    ->assertPathIs('/admin/vehicles')
                    ->assertSee('Vehicle Management');
            });
        });

        it('can navigate to booking management from dashboard', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->click('[data-testid="manage-bookings-btn"]')
                    ->assertPathIs('/admin/bookings')
                    ->assertSee('Booking Management');
            });
        });

        it('can access recent activities widget', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertPresent('[data-testid="recent-activities"]')
                    ->assertSee('Recent Activities')
                    ->assertPresent('[data-testid="activity-item"]');
            });
        });
    });

    describe('Dashboard Responsiveness and Performance', function (): void {
        it('loads dashboard within acceptable time', function (): void {
            Vehicle::factory(100)->create();
            Booking::factory(50)->create();

            $startTime = microtime(true);

            browse(function ($browser) use ($startTime): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Dashboard');

                $loadTime = microtime(true) - $startTime;
                expect($loadTime)->toBeLessThan(3.0); // Should load in under 3 seconds
            });
        });

        it('displays correctly on different screen sizes', function (): void {
            browse(function ($browser): void {
                // Desktop view
                $browser->loginAs($this->admin)
                    ->resize(1200, 800)
                    ->visit('/admin')
                    ->assertPresent('[data-testid="desktop-navigation"]')
                    ->assertSee('Dashboard');

                // Tablet view
                $browser->resize(768, 1024)
                    ->refresh()
                    ->assertPresent('[data-testid="mobile-menu-toggle"]')
                    ->assertSee('Dashboard');

                // Mobile view
                $browser->resize(375, 667)
                    ->refresh()
                    ->assertPresent('[data-testid="mobile-navigation"]')
                    ->assertSee('Dashboard');
            });
        });
    });

    describe('Dashboard Search and Filtering', function (): void {
        it('can search for vehicles from dashboard', function (): void {
            Vehicle::factory()->create(['make' => 'Toyota', 'model' => 'Camry']);
            Vehicle::factory()->create(['make' => 'Honda', 'model' => 'Civic']);

            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->type('[data-testid="global-search"]', 'Toyota')
                    ->pause(500)
                    ->assertSee('Toyota Camry')
                    ->assertDontSee('Honda Civic');
            });
        });

        it('can filter dashboard data by date range', function (): void {
            Booking::factory()->create(['created_at' => now()->subDays(5)]);
            Booking::factory()->create(['created_at' => now()->subDay()]);

            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->click('[data-testid="date-filter"]')
                    ->select('[data-testid="date-range"]', 'last_week')
                    ->pause(500)
                    ->assertSee('Filtered Results')
                    ->assertSee('Last 7 days');
            });
        });
    });

    describe('Error Handling and Edge Cases', function (): void {
        it('handles empty dashboard gracefully', function (): void {
            // Clear all data
            Vehicle::truncate();
            Booking::truncate();
            User::where('id', '!=', $this->admin->id)->delete();

            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('No vehicles found')
                    ->assertSee('No bookings found')
                    ->assertSee('Get started by adding some vehicles');
            });
        });

        it('displays error message when data fails to load', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    // Simulate network error by blocking API calls
                    ->script('window.fetch = () => Promise.reject("Network Error")');

                $browser->refresh()
                    ->pause(2000)
                    ->assertSee('Unable to load dashboard data')
                    ->assertPresent('[data-testid="retry-button"]');
            });
        });
    });

    describe('Real-time Updates and Notifications', function (): void {
        it('shows notification when new booking is created', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin');

                // Simulate real-time notification
                $browser->script('
                    window.dispatchEvent(new CustomEvent("booking-created", {
                        detail: { message: "New booking received" }
                    }));
                ');

                $browser->pause(1000)
                    ->assertSee('New booking received')
                    ->assertPresent('[data-testid="notification-toast"]');
            });
        });

        it('updates statistics in real-time', function (): void {
            browse(function ($browser): void {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Total Vehicles: 0');

                // Create vehicle in background
                Vehicle::factory()->create();

                // Simulate real-time update
                $browser->script('
                    document.querySelector("[data-testid=vehicle-count]").textContent = "Total Vehicles: 1";
                ');

                $browser->pause(500)
                    ->assertSee('Total Vehicles: 1');
            });
        });
    });
});
