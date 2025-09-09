<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Authentication Browser Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123')
        ]);
        $this->owner = User::factory()->owner()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('password123')
        ]);
        $this->renter = User::factory()->renter()->create([
            'email' => 'renter@example.com',
            'password' => Hash::make('password123')
        ]);
    });

    describe('User Login Process', function () {
        it('can login with valid credentials', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    ->assertSee('Sign in to your account')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/admin')
                    ->assertSee('Dashboard')
                    ->assertSee('Welcome');
            });
        });

        it('shows error with invalid credentials', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'wrongpassword')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/login')
                    ->assertSee('These credentials do not match our records')
                    ->assertPresent('[data-testid="error-message"]');
            });
        });

        it('validates required fields', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    ->click('[data-testid="login-btn"]')
                    ->pause(1000)
                    ->assertSee('The email field is required')
                    ->assertSee('The password field is required')
                    ->assertPathIs('/login');
            });
        });

        it('can login with remember me option', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->check('[data-testid="remember"]')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/admin');

                // Verify remember cookie is set
                $cookies = $browser->driver->manage()->getCookies();
                $rememberCookie = collect($cookies)->first(function ($cookie) {
                    return str_contains($cookie['name'], 'remember_');
                });
                
                expect($rememberCookie)->not()->toBeNull();
            });
        });
    });

    describe('User Registration Process', function () {
        it('can register a new user account', function () {
            browse(function ($browser) {
                $browser->visit('/register')
                    ->assertSee('Create your account')
                    ->type('[data-testid="name"]', 'John Doe')
                    ->type('[data-testid="email"]', 'john@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->type('[data-testid="password_confirmation"]', 'password123')
                    ->select('[data-testid="role"]', 'renter')
                    ->click('[data-testid="register-btn"]')
                    ->pause(3000)
                    ->assertSee('Registration successful')
                    ->assertSee('Please verify your email address');
            });
        });

        it('validates registration form fields', function () {
            browse(function ($browser) {
                $browser->visit('/register')
                    ->click('[data-testid="register-btn"]')
                    ->pause(1000)
                    ->assertSee('The name field is required')
                    ->assertSee('The email field is required')
                    ->assertSee('The password field is required');
            });
        });

        it('validates password confirmation', function () {
            browse(function ($browser) {
                $browser->visit('/register')
                    ->type('[data-testid="name"]', 'John Doe')
                    ->type('[data-testid="email"]', 'john@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->type('[data-testid="password_confirmation"]', 'differentpassword')
                    ->click('[data-testid="register-btn"]')
                    ->pause(1000)
                    ->assertSee('The password confirmation does not match')
                    ->assertPathIs('/register');
            });
        });

        it('validates unique email address', function () {
            browse(function ($browser) {
                $browser->visit('/register')
                    ->type('[data-testid="name"]', 'John Doe')
                    ->type('[data-testid="email"]', 'admin@example.com') // Already exists
                    ->type('[data-testid="password"]', 'password123')
                    ->type('[data-testid="password_confirmation"]', 'password123')
                    ->click('[data-testid="register-btn"]')
                    ->pause(1000)
                    ->assertSee('The email has already been taken')
                    ->assertPathIs('/register');
            });
        });

        it('can register with different user roles', function () {
            browse(function ($browser) {
                // Register as owner
                $browser->visit('/register')
                    ->type('[data-testid="name"]', 'Vehicle Owner')
                    ->type('[data-testid="email"]', 'vehicleowner@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->type('[data-testid="password_confirmation"]', 'password123')
                    ->select('[data-testid="role"]', 'owner')
                    ->click('[data-testid="register-btn"]')
                    ->pause(3000)
                    ->assertSee('Registration successful');
            });
        });
    });

    describe('Password Reset Process', function () {
        it('can request password reset', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    ->click('[data-testid="forgot-password-link"]')
                    ->assertPathIs('/forgot-password')
                    ->assertSee('Forgot your password?')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->click('[data-testid="send-reset-link"]')
                    ->pause(2000)
                    ->assertSee('We have emailed your password reset link')
                    ->assertPresent('[data-testid="success-message"]');
            });
        });

        it('validates email for password reset', function () {
            browse(function ($browser) {
                $browser->visit('/forgot-password')
                    ->type('[data-testid="email"]', 'nonexistent@example.com')
                    ->click('[data-testid="send-reset-link"]')
                    ->pause(2000)
                    ->assertSee('We can\'t find a user with that email address')
                    ->assertPathIs('/forgot-password');
            });
        });

        it('can reset password with valid token', function () {
            // Create a password reset token manually for testing
            $token = \Illuminate\Support\Str::random(60);
            \DB::table('password_reset_tokens')->insert([
                'email' => 'admin@example.com',
                'token' => Hash::make($token),
                'created_at' => now()
            ]);

            browse(function ($browser) use ($token) {
                $browser->visit("/reset-password/$token?email=admin@example.com")
                    ->assertSee('Reset Password')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'newpassword123')
                    ->type('[data-testid="password_confirmation"]', 'newpassword123')
                    ->click('[data-testid="reset-password-btn"]')
                    ->pause(3000)
                    ->assertSee('Your password has been reset')
                    ->assertPathIs('/admin');
            });
        });
    });

    describe('Email Verification Process', function () {
        it('prompts unverified users to verify email', function () {
            $unverifiedUser = User::factory()->unverified()->create([
                'email' => 'unverified@example.com',
                'password' => Hash::make('password123')
            ]);

            browse(function ($browser) {
                $browser->visit('/login')
                    ->type('[data-testid="email"]', 'unverified@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/email/verify')
                    ->assertSee('Verify Your Email Address')
                    ->assertSee('Before proceeding, please check your email');
            });
        });

        it('can resend verification email', function () {
            $unverifiedUser = User::factory()->unverified()->create([
                'email' => 'unverified@example.com',
                'password' => Hash::make('password123')
            ]);

            browse(function ($browser) {
                $browser->loginAs($unverifiedUser)
                    ->visit('/email/verify')
                    ->click('[data-testid="resend-verification"]')
                    ->pause(2000)
                    ->assertSee('A fresh verification link has been sent')
                    ->assertPresent('[data-testid="success-message"]');
            });
        });

        it('verifies email with valid verification link', function () {
            $unverifiedUser = User::factory()->unverified()->create();
            $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $unverifiedUser->id, 'hash' => sha1($unverifiedUser->email)]
            );

            browse(function ($browser) use ($verificationUrl) {
                $browser->visit($verificationUrl)
                    ->pause(2000)
                    ->assertSee('Email verified successfully')
                    ->assertPathIs('/admin');
            });
        });
    });

    describe('User Logout Process', function () {
        it('can logout successfully', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Dashboard')
                    ->click('[data-testid="user-menu"]')
                    ->pause(500)
                    ->click('[data-testid="logout-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/login')
                    ->assertSee('Sign in to your account');
            });
        });

        it('redirects to login when accessing protected routes after logout', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->click('[data-testid="user-menu"]')
                    ->click('[data-testid="logout-btn"]')
                    ->pause(2000)
                    ->visit('/admin/vehicles')
                    ->assertPathIs('/login')
                    ->assertSee('Please log in to access this page');
            });
        });
    });

    describe('Role-Based Access Control', function () {
        it('redirects users to appropriate dashboard based on role', function () {
            browse(function ($browser) {
                // Admin access
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Admin Dashboard')
                    ->assertSee('System Overview');

                $browser->logout();

                // Owner access  
                $browser->loginAs($this->owner)
                    ->visit('/admin')
                    ->assertSee('Owner Dashboard')
                    ->assertSee('My Vehicles');

                $browser->logout();

                // Renter access
                $browser->loginAs($this->renter)
                    ->visit('/admin')
                    ->assertSee('Renter Dashboard')
                    ->assertSee('My Bookings');
            });
        });

        it('restricts access to admin-only features', function () {
            browse(function ($browser) {
                $browser->loginAs($this->renter)
                    ->visit('/admin/users')
                    ->assertSee('Unauthorized')
                    ->assertPathIs('/admin/unauthorized');
            });
        });

        it('allows owners to manage their own content only', function () {
            browse(function ($browser) {
                $browser->loginAs($this->owner)
                    ->visit('/admin/vehicles')
                    ->assertSee('My Vehicles')
                    ->assertPresent('[data-testid="add-vehicle-btn"]')
                    ->assertDontSee('All Vehicles'); // Should not see admin view
            });
        });
    });

    describe('Session Management and Security', function () {
        it('maintains session across page navigation', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertAuthenticatedAs($this->admin)
                    ->visit('/admin/vehicles')
                    ->assertAuthenticatedAs($this->admin)
                    ->visit('/admin/bookings')
                    ->assertAuthenticatedAs($this->admin);
            });
        });

        it('handles session timeout appropriately', function () {
            // Simulate session timeout by clearing session
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Dashboard');

                // Clear session cookies to simulate timeout
                $browser->driver->manage()->deleteAllCookies();

                $browser->refresh()
                    ->pause(2000)
                    ->assertPathIs('/login')
                    ->assertSee('Your session has expired');
            });
        });

        it('prevents CSRF attacks', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    ->pause(1000);

                // Try to submit form without CSRF token
                $browser->script('
                    document.querySelector("input[name=_token]").remove();
                ');

                $browser->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertSee('Page Expired')
                    ->assertPathIs('/login');
            });
        });

        it('handles concurrent logins correctly', function () {
            browse(function ($browser1, $browser2) {
                // Login with same user in two browsers
                $browser1->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Dashboard');

                $browser2->loginAs($this->admin)
                    ->visit('/admin')
                    ->assertSee('Dashboard');

                // Both should remain logged in (unless single session is enforced)
                $browser1->refresh()
                    ->assertSee('Dashboard');
            });
        });
    });

    describe('User Profile Management', function () {
        it('can access and update user profile', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin')
                    ->click('[data-testid="user-menu"]')
                    ->click('[data-testid="profile-link"]')
                    ->assertPathIs('/admin/profile')
                    ->assertSee('Profile Information')
                    ->assertInputValue('[data-testid="name"]', $this->admin->name)
                    ->clear('[data-testid="name"]')
                    ->type('[data-testid="name"]', 'Updated Admin Name')
                    ->click('[data-testid="update-profile"]')
                    ->pause(2000)
                    ->assertSee('Profile updated successfully')
                    ->assertInputValue('[data-testid="name"]', 'Updated Admin Name');
            });
        });

        it('can change password', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/profile')
                    ->type('[data-testid="current_password"]', 'password123')
                    ->type('[data-testid="new_password"]', 'newpassword123')
                    ->type('[data-testid="new_password_confirmation"]', 'newpassword123')
                    ->click('[data-testid="update-password"]')
                    ->pause(2000)
                    ->assertSee('Password updated successfully');
            });
        });

        it('validates current password when changing password', function () {
            browse(function ($browser) {
                $browser->loginAs($this->admin)
                    ->visit('/admin/profile')
                    ->type('[data-testid="current_password"]', 'wrongpassword')
                    ->type('[data-testid="new_password"]', 'newpassword123')
                    ->type('[data-testid="new_password_confirmation"]', 'newpassword123')
                    ->click('[data-testid="update-password"]')
                    ->pause(2000)
                    ->assertSee('The current password is incorrect')
                    ->assertPathIs('/admin/profile');
            });
        });
    });

    describe('Mobile Authentication Experience', function () {
        it('displays authentication forms correctly on mobile', function () {
            browse(function ($browser) {
                $browser->resize(375, 667) // iPhone SE size
                    ->visit('/login')
                    ->assertPresent('[data-testid="mobile-login-form"]')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertPathIs('/admin')
                    ->assertPresent('[data-testid="mobile-navigation"]');
            });
        });

        it('handles mobile registration flow', function () {
            browse(function ($browser) {
                $browser->resize(375, 667)
                    ->visit('/register')
                    ->assertPresent('[data-testid="mobile-register-form"]')
                    ->type('[data-testid="name"]', 'Mobile User')
                    ->type('[data-testid="email"]', 'mobile@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->type('[data-testid="password_confirmation"]', 'password123')
                    ->select('[data-testid="role"]', 'renter')
                    ->click('[data-testid="register-btn"]')
                    ->pause(3000)
                    ->assertSee('Registration successful');
            });
        });
    });

    describe('Error Handling and Recovery', function () {
        it('handles server errors gracefully during login', function () {
            browse(function ($browser) {
                $browser->visit('/login')
                    // Simulate server error
                    ->script('
                        document.querySelector("form").addEventListener("submit", function(e) {
                            e.preventDefault();
                            throw new Error("Server Error");
                        });
                    ')
                    ->type('[data-testid="email"]', 'admin@example.com')
                    ->type('[data-testid="password"]', 'password123')
                    ->click('[data-testid="login-btn"]')
                    ->pause(2000)
                    ->assertSee('An error occurred. Please try again.')
                    ->assertPresent('[data-testid="retry-button"]');
            });
        });

        it('provides clear error messages for various scenarios', function () {
            browse(function ($browser) {
                // Invalid email format
                $browser->visit('/login')
                    ->type('[data-testid="email"]', 'invalid-email')
                    ->type('[data-testid="password"]', 'password123')
                    ->click('[data-testid="login-btn"]')
                    ->pause(1000)
                    ->assertSee('Please enter a valid email address');
            });
        });
    });
});