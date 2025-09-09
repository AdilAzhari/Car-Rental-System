<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Management', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('User Listing', function () {
        it('allows admin to view all users', function () {
            User::factory(5)->create();
            
            $this->actingAs($this->admin)
                ->get('/admin/users')
                ->assertSuccessful()
                ->assertSee('Users');
        });

        it('restricts owner access to user management', function () {
            $this->actingAs($this->owner)
                ->get('/admin/users')
                ->assertForbidden();
        });

        it('restricts renter access to user management', function () {
            $this->actingAs($this->renter)
                ->get('/admin/users')
                ->assertForbidden();
        });

        it('filters users by role', function () {
            User::factory()->admin()->create();
            User::factory()->owner()->create();
            
            $this->actingAs($this->admin)
                ->get('/admin/users?role=admin')
                ->assertSuccessful();
        });

        it('searches users by name or email', function () {
            User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
            
            $this->actingAs($this->admin)
                ->get('/admin/users?search=john')
                ->assertSuccessful();
        });
    });

    describe('User Creation', function () {
        it('allows admin to create new users', function () {
            $userData = [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => UserRole::RENTER->value,
                'phone' => '+60123456789'
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/users', $userData);
            
            $user = User::where('email', 'newuser@example.com')->first();
            expect($user->name)->toBe('New User');
            expect($user->role)->toBe(UserRole::RENTER);
        });

        it('validates unique email addresses', function () {
            $userData = [
                'name' => 'Duplicate User',
                'email' => $this->renter->email, // Existing email
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => UserRole::RENTER->value
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/users', $userData)
                ->assertSessionHasErrors(['email']);
        });

        it('validates password confirmation', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'differentpassword',
                'role' => UserRole::RENTER->value
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/users', $userData)
                ->assertSessionHasErrors(['password']);
        });

        it('hashes passwords correctly', function () {
            $userData = [
                'name' => 'Password Test',
                'email' => 'password@example.com',
                'password' => 'testpassword',
                'password_confirmation' => 'testpassword',
                'role' => UserRole::RENTER->value
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/users', $userData);
            
            $user = User::where('email', 'password@example.com')->first();
            expect(Hash::check('testpassword', $user->password))->toBeTrue();
        });
    });

    describe('User Viewing', function () {
        it('allows admin to view any user profile', function () {
            $this->actingAs($this->admin)
                ->get("/admin/users/{$this->renter->id}")
                ->assertSuccessful();
        });

        it('allows users to view their own profile', function () {
            $this->actingAs($this->renter)
                ->get("/admin/users/{$this->renter->id}")
                ->assertSuccessful();
        });

        it('prevents users from viewing other users profiles', function () {
            $otherRenter = User::factory()->renter()->create();
            
            $this->actingAs($this->renter)
                ->get("/admin/users/{$otherRenter->id}")
                ->assertForbidden();
        });
    });

    describe('User Updates', function () {
        it('allows admin to update any user', function () {
            $updateData = [
                'name' => 'Updated Name',
                'phone' => '+60987654321'
            ];
            
            $this->actingAs($this->admin)
                ->patch("/admin/users/{$this->renter->id}", $updateData);
            
            $this->renter->refresh();
            expect($this->renter->name)->toBe('Updated Name');
            expect($this->renter->phone)->toBe('+60987654321');
        });

        it('allows users to update their own profile', function () {
            $updateData = [
                'name' => 'Self Updated',
                'phone' => '+60111111111'
            ];
            
            $this->actingAs($this->renter)
                ->patch("/admin/users/{$this->renter->id}", $updateData);
            
            $this->renter->refresh();
            expect($this->renter->name)->toBe('Self Updated');
        });

        it('prevents users from updating other users', function () {
            $otherRenter = User::factory()->renter()->create();
            $updateData = ['name' => 'Unauthorized Update'];
            
            $this->actingAs($this->renter)
                ->patch("/admin/users/{$otherRenter->id}", $updateData)
                ->assertForbidden();
        });

        it('allows admin to change user roles', function () {
            $this->actingAs($this->admin)
                ->patch("/admin/users/{$this->renter->id}", [
                    'role' => UserRole::OWNER->value
                ]);
            
            $this->renter->refresh();
            expect($this->renter->role)->toBe(UserRole::OWNER);
        });

        it('prevents non-admin from changing roles', function () {
            $this->actingAs($this->renter)
                ->patch("/admin/users/{$this->renter->id}", [
                    'role' => UserRole::ADMIN->value
                ])
                ->assertSessionHasErrors(['role']);
        });
    });

    describe('Password Management', function () {
        it('allows password updates with proper validation', function () {
            $passwordData = [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123'
            ];
            
            $this->actingAs($this->renter)
                ->patch("/admin/users/{$this->renter->id}/password", $passwordData);
            
            $this->renter->refresh();
            expect(Hash::check('newpassword123', $this->renter->password))->toBeTrue();
        });

        it('validates current password before update', function () {
            $passwordData = [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123'
            ];
            
            $this->actingAs($this->renter)
                ->patch("/admin/users/{$this->renter->id}/password", $passwordData)
                ->assertSessionHasErrors(['current_password']);
        });

        it('allows admin to reset any user password', function () {
            $passwordData = [
                'password' => 'adminreset123',
                'password_confirmation' => 'adminreset123'
            ];
            
            $this->actingAs($this->admin)
                ->patch("/admin/users/{$this->renter->id}/reset-password", $passwordData);
            
            $this->renter->refresh();
            expect(Hash::check('adminreset123', $this->renter->password))->toBeTrue();
        });
    });

    describe('User Activity Tracking', function () {
        it('displays user login history', function () {
            $this->actingAs($this->admin)
                ->get("/admin/users/{$this->renter->id}/activity")
                ->assertSuccessful();
        });

        it('shows user booking history', function () {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
            Booking::factory(3)->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            $this->actingAs($this->admin)
                ->get("/admin/users/{$this->renter->id}/bookings")
                ->assertSuccessful();
        });

        it('shows owner vehicle statistics', function () {
            Vehicle::factory(2)->create(['owner_id' => $this->owner->id]);
            
            $this->actingAs($this->admin)
                ->get("/admin/users/{$this->owner->id}/vehicles")
                ->assertSuccessful();
        });
    });

    describe('User Deletion', function () {
        it('allows admin to soft delete users', function () {
            $userId = $this->renter->id;
            
            $this->actingAs($this->admin)
                ->delete("/admin/users/{$userId}");
            
            expect(User::find($userId))->toBeNull();
            expect(User::withTrashed()->find($userId))->not->toBeNull();
        });

        it('prevents deletion of users with active bookings', function () {
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
            Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $vehicle->id
            ]);
            
            $this->actingAs($this->admin)
                ->delete("/admin/users/{$this->renter->id}")
                ->assertSessionHasErrors();
        });

        it('prevents self-deletion', function () {
            $this->actingAs($this->admin)
                ->delete("/admin/users/{$this->admin->id}")
                ->assertForbidden();
        });
    });

    describe('Email Verification', function () {
        it('handles email verification status', function () {
            $unverifiedUser = User::factory()->unverified()->create();
            
            $this->actingAs($this->admin)
                ->patch("/admin/users/{$unverifiedUser->id}", [
                    'email_verified_at' => now()
                ]);
            
            $unverifiedUser->refresh();
            expect($unverifiedUser->email_verified_at)->not->toBeNull();
        });

        it('allows resending verification emails', function () {
            $unverifiedUser = User::factory()->unverified()->create();
            
            $this->actingAs($this->admin)
                ->post("/admin/users/{$unverifiedUser->id}/resend-verification");
            
            // Would test email sending in integration tests
        });
    });

    describe('User Profile Completion', function () {
        it('tracks profile completion status', function () {
            $incompleteUser = User::factory()->create([
                'phone' => null,
                'address' => null
            ]);
            
            $this->actingAs($this->admin)
                ->get("/admin/users/{$incompleteUser->id}")
                ->assertSuccessful();
        });

        it('validates required profile fields', function () {
            $this->actingAs($this->renter)
                ->patch("/admin/users/{$this->renter->id}", [
                    'phone' => '', // Required field
                ])
                ->assertSessionHasErrors(['phone']);
        });
    });
});