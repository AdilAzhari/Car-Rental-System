<?php

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Model', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::RENTER->value,
        ]);
    });

    it('has correct fillable attributes', function (): void {
        $fillable = [
            'name',
            'email',
            'password',
            'phone',
            'role',
            'status',
            'license_number',
            'id_document_path',
            'license_document_path',
            'avatar',
            'is_verified',
            'date_of_birth',
            'address',
            'is_new_user',
            'has_changed_default_password',
            'last_login_at',
            'password_changed_at',
        ];

        expect($this->user->getFillable())->toEqual($fillable);
    });

    it('has correct hidden attributes', function (): void {
        $hidden = ['password', 'remember_token'];

        expect($this->user->getHidden())->toEqual($hidden);
    });

    it('casts attributes correctly', function (): void {
        expect($this->user->role)->toBeInstanceOf(UserRole::class);

        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);
        expect($unverifiedUser->email_verified_at)->toBeNull();

        $userWithoutDob = User::factory()->create(['date_of_birth' => null]);
        expect($userWithoutDob->date_of_birth)->toBeNull();
    });

    it('hashes password when set', function (): void {
        $user = User::factory()->create(['password' => 'password123']);

        expect(Hash::check('password123', $user->password))->toBeTrue();
        expect($user->password)->not->toBe('password123');
    });

    it('can be created with different roles', function (): void {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();

        expect($admin->role)->toBe(UserRole::ADMIN);
        expect($owner->role)->toBe(UserRole::OWNER);
        expect($renter->role)->toBe(UserRole::RENTER);
    });

    it('owner can have many vehicles', function (): void {
        $owner = User::factory()->owner()->create();
        Vehicle::factory(3)->create(['owner_id' => $owner->id]);

        expect($owner->vehicles)->toHaveCount(3);
        expect($owner->vehicles->first())->toBeInstanceOf(Vehicle::class);
    });

    it('renter can have many bookings', function (): void {
        $renter = User::factory()->renter()->create();
        $vehicle = Vehicle::factory()->create();

        Booking::factory(2)->create([
            'renter_id' => $renter->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($renter->bookings)->toHaveCount(2);
        expect($renter->bookings->first())->toBeInstanceOf(Booking::class);
    });

    it('renter can have many reviews', function (): void {
        $renter = User::factory()->renter()->create();
        $vehicle = Vehicle::factory()->create();

        Review::factory(2)->create([
            'renter_id' => $renter->id,
            'vehicle_id' => $vehicle->id,
        ]);

        expect($renter->reviews)->toHaveCount(2);
        expect($renter->reviews->first())->toBeInstanceOf(Review::class);
    });

    it('has unique email constraint', function (): void {
        expect(function (): void {
            User::factory()->create(['email' => $this->user->email]);
        })->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can update profile information', function (): void {
        $this->user->update([
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'address' => '123 Main St',
        ]);

        expect($this->user->name)->toBe('Jane Doe');
        expect($this->user->phone)->toBe('+1234567890');
        expect($this->user->address)->toBe('123 Main St');
    });

    it('uses email verification', function (): void {
        $user = User::factory()->create(['email_verified_at' => null]);

        expect($user->hasVerifiedEmail())->toBeFalse();

        $user->markEmailAsVerified();

        expect($user->hasVerifiedEmail())->toBeTrue();
    });
});
