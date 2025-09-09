<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Review;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Model', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::RENTER->value
        ]);
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'name', 'email', 'password', 'role', 'phone', 'address',
            'date_of_birth', 'emergency_contact', 'license_number',
            'profile_photo_path'
        ];
        
        expect($this->user->getFillable())->toEqual($fillable);
    });

    it('has correct hidden attributes', function () {
        $hidden = ['password', 'remember_token'];
        
        expect($this->user->getHidden())->toEqual($hidden);
    });

    it('casts attributes correctly', function () {
        expect($this->user->role)->toBeInstanceOf(UserRole::class);
        expect($this->user->email_verified_at)->toBeNull();
        expect($this->user->date_of_birth)->toBeNull();
    });

    it('hashes password when set', function () {
        $user = User::factory()->create(['password' => 'password123']);
        
        expect(Hash::check('password123', $user->password))->toBeTrue();
        expect($user->password)->not->toBe('password123');
    });

    it('can be created with different roles', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        
        expect($admin->role)->toBe(UserRole::ADMIN);
        expect($owner->role)->toBe(UserRole::OWNER);
        expect($renter->role)->toBe(UserRole::RENTER);
    });

    it('owner can have many vehicles', function () {
        $owner = User::factory()->owner()->create();
        Vehicle::factory(3)->create(['owner_id' => $owner->id]);
        
        expect($owner->vehicles)->toHaveCount(3);
        expect($owner->vehicles->first())->toBeInstanceOf(Vehicle::class);
    });

    it('renter can have many bookings', function () {
        $renter = User::factory()->renter()->create();
        $vehicle = Vehicle::factory()->create();
        
        Booking::factory(2)->create([
            'renter_id' => $renter->id,
            'vehicle_id' => $vehicle->id
        ]);
        
        expect($renter->bookings)->toHaveCount(2);
        expect($renter->bookings->first())->toBeInstanceOf(Booking::class);
    });

    it('renter can have many reviews', function () {
        $renter = User::factory()->renter()->create();
        $vehicle = Vehicle::factory()->create();
        
        Review::factory(2)->create([
            'renter_id' => $renter->id,
            'vehicle_id' => $vehicle->id
        ]);
        
        expect($renter->reviews)->toHaveCount(2);
        expect($renter->reviews->first())->toBeInstanceOf(Review::class);
    });

    it('has unique email constraint', function () {
        expect(function () {
            User::factory()->create(['email' => $this->user->email]);
        })->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can update profile information', function () {
        $this->user->update([
            'name' => 'Jane Doe',
            'phone' => '+1234567890',
            'address' => '123 Main St'
        ]);
        
        expect($this->user->name)->toBe('Jane Doe');
        expect($this->user->phone)->toBe('+1234567890');
        expect($this->user->address)->toBe('123 Main St');
    });

    it('uses email verification', function () {
        $user = User::factory()->create(['email_verified_at' => null]);
        
        expect($user->hasVerifiedEmail())->toBeFalse();
        
        $user->markEmailAsVerified();
        
        expect($user->hasVerifiedEmail())->toBeTrue();
    });
});