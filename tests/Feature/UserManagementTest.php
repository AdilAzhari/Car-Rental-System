<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('User Management', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
    });

    describe('User Listing', function (): void {
        it('allows admin to view all users in Filament', function (): void {
            $users = User::factory(5)->create();

            $this->actingAs($this->admin);

            Livewire::test(ListUsers::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords($users);
        });

        it('displays users with different roles', function (): void {
            $adminUser = User::factory()->admin()->create();
            $ownerUser = User::factory()->owner()->create();
            $renterUser = User::factory()->renter()->create();

            $this->actingAs($this->admin);

            Livewire::test(ListUsers::class)
                ->assertSuccessful()
                ->assertCanSeeTableRecords([$adminUser, $ownerUser, $renterUser]);
        });

        it('can search users by name or email', function (): void {
            $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
            User::factory(3)->create(); // Other users

            $this->actingAs($this->admin);

            Livewire::test(ListUsers::class)
                ->searchTable('john@example.com')
                ->assertCanSeeTableRecords([$user])
                ->assertCountTableRecords(1);
        });
    });

    describe('User Creation', function (): void {
        it('allows admin to create new users via Filament', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(CreateUser::class)
                ->fillForm([
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'role' => UserRole::RENTER->value,
                    'phone' => '+60123456789',
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $user = User::where('email', 'newuser@example.com')->first();
            expect($user)->not->toBeNull()
                ->and($user->name)->toBe('New User')
                ->and($user->role)->toBe(UserRole::RENTER);
        });

        it('validates unique email addresses', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(CreateUser::class)
                ->fillForm([
                    'name' => 'Duplicate User',
                    'email' => $this->renter->email, // Existing email
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'role' => UserRole::RENTER->value,
                ])
                ->call('create')
                ->assertHasFormErrors(['email']);
        });

        it('creates users with default password when none provided', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(CreateUser::class)
                ->fillForm([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'role' => UserRole::RENTER->value,
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $user = User::where('email', 'test@example.com')->first();
            expect($user)->not->toBeNull()
                ->and($user->name)->toBe('Test User')
                ->and($user->role)->toBe(UserRole::RENTER);
        });

        it('handles user creation correctly via Filament', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(CreateUser::class)
                ->fillForm([
                    'name' => 'Password Test',
                    'email' => 'password@example.com',
                    'role' => UserRole::RENTER->value,
                    'is_verified' => true,
                    'is_active' => true,
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $user = User::where('email', 'password@example.com')->first();
            expect($user)->not->toBeNull()
                ->and($user->name)->toBe('Password Test')
                ->and($user->role)->toBe(UserRole::RENTER)
                ->and($user->is_verified)->toBeTrue();
        });
    });

    describe('User Editing', function (): void {
        it('allows admin to edit any user via Filament', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(EditUser::class, ['record' => $this->renter->getRouteKey()])
                ->assertSuccessful()
                ->assertFormSet([
                    'name' => $this->renter->name,
                    'email' => $this->renter->email,
                ]);
        });

        it('allows admin to update user information', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(EditUser::class, ['record' => $this->renter->getRouteKey()])
                ->fillForm([
                    'name' => 'Updated Name',
                    'phone' => '+60987654321',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->renter->refresh();
            expect($this->renter->name)->toBe('Updated Name')
                ->and($this->renter->phone)->toBe('+60987654321');
        });

        it('allows admin to change user roles', function (): void {
            $this->actingAs($this->admin);

            Livewire::test(EditUser::class, ['record' => $this->renter->getRouteKey()])
                ->fillForm([
                    'role' => UserRole::OWNER->value,
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->renter->refresh();
            expect($this->renter->role)->toBe(UserRole::OWNER);
        });
    });

    describe('User Model Functionality', function (): void {
        it('handles user role changes', function (): void {
            expect($this->renter->role)->toBe(UserRole::RENTER);

            $this->renter->update(['role' => UserRole::OWNER->value]);
            $this->renter->refresh();

            expect($this->renter->role)->toBe(UserRole::OWNER);
        });

        it('can validate password correctly', function (): void {
            $user = User::factory()->create(['password' => Hash::make('testpassword')]);

            expect(Hash::check('testpassword', $user->password))->toBeTrue()
                ->and(Hash::check('wrongpassword', $user->password))->toBeFalse();
        });

        it('handles soft deletion correctly', function (): void {
            $userId = $this->renter->id;

            $this->renter->delete();

            expect(User::find($userId))->toBeNull()
                ->and(User::withTrashed()->find($userId))->not->toBeNull();
        });

        it('tracks user relationships correctly', function (): void {
            // Test user has bookings relationship
            $vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
            $booking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $vehicle->id,
            ]);

            expect($this->renter->bookings)->toHaveCount(1)
                ->and($this->renter->bookings->first()->id)->toBe($booking->id);

            // Test owner has vehicles relationship
            expect($this->owner->vehicles)->toHaveCount(1)
                ->and($this->owner->vehicles->first()->id)->toBe($vehicle->id);
        });

        it('validates email uniqueness', function (): void {
            expect(function (): void {
                User::factory()->create(['email' => $this->renter->email]);
            })->toThrow(Exception::class);
        });
    });
});
