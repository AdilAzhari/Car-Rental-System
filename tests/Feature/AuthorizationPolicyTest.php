<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;

describe('User Authorization Policy', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->owner = User::factory()->create(['role' => UserRole::OWNER]);
        $this->renter = User::factory()->create(['role' => UserRole::RENTER]);
        $this->userPolicy = new UserPolicy;
    });

    it('allows admins to view any users', function (): void {
        expect($this->userPolicy->viewAny($this->admin))->toBeTrue();
        expect($this->userPolicy->viewAny($this->owner))->toBeFalse();
        expect($this->userPolicy->viewAny($this->renter))->toBeFalse();
    });

    it('allows admins to create users', function (): void {
        expect($this->userPolicy->create($this->admin))->toBeTrue();
        expect($this->userPolicy->create($this->owner))->toBeFalse();
        expect($this->userPolicy->create($this->renter))->toBeFalse();
    });

    it('allows admins to delete users except themselves', function (): void {
        expect($this->userPolicy->delete($this->admin, $this->owner))->toBeTrue();
        expect($this->userPolicy->delete($this->admin, $this->admin))->toBeFalse();
        expect($this->userPolicy->delete($this->owner, $this->renter))->toBeFalse();
    });

    it('allows users to view their own profile', function (): void {
        expect($this->userPolicy->view($this->owner, $this->owner))->toBeTrue();
        expect($this->userPolicy->view($this->renter, $this->renter))->toBeTrue();
        expect($this->userPolicy->view($this->owner, $this->renter))->toBeFalse();
    });
});

describe('Vehicle Authorization Policy', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->owner = User::factory()->create(['role' => UserRole::OWNER]);
        $this->renter = User::factory()->create(['role' => UserRole::RENTER]);

        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->vehiclePolicy = new VehiclePolicy;
    });

    it('allows admins and owners to create vehicles', function (): void {
        expect($this->vehiclePolicy->create($this->admin))->toBeTrue();
        expect($this->vehiclePolicy->create($this->owner))->toBeTrue();
        expect($this->vehiclePolicy->create($this->renter))->toBeFalse();
    });

    it('allows admins and vehicle owners to update vehicles', function (): void {
        expect($this->vehiclePolicy->update($this->admin, $this->vehicle))->toBeTrue();
        expect($this->vehiclePolicy->update($this->owner, $this->vehicle))->toBeTrue();

        $otherOwner = User::factory()->create(['role' => UserRole::OWNER]);
        expect($this->vehiclePolicy->update($otherOwner, $this->vehicle))->toBeFalse();
        expect($this->vehiclePolicy->update($this->renter, $this->vehicle))->toBeFalse();
    });

    it('allows admins and vehicle owners to delete vehicles', function (): void {
        expect($this->vehiclePolicy->delete($this->admin, $this->vehicle))->toBeTrue();
        expect($this->vehiclePolicy->delete($this->owner, $this->vehicle))->toBeTrue();

        $otherOwner = User::factory()->create(['role' => UserRole::OWNER]);
        expect($this->vehiclePolicy->delete($otherOwner, $this->vehicle))->toBeFalse();
        expect($this->vehiclePolicy->delete($this->renter, $this->vehicle))->toBeFalse();
    });

    it('allows everyone to view published vehicles', function (): void {
        $publishedVehicle = Vehicle::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'published',
        ]);

        expect($this->vehiclePolicy->view($this->admin, $publishedVehicle))->toBeTrue();
        expect($this->vehiclePolicy->view($this->owner, $publishedVehicle))->toBeTrue();
        expect($this->vehiclePolicy->view($this->renter, $publishedVehicle))->toBeTrue();
    });

    it('only allows admin to permanently delete vehicles', function (): void {
        expect($this->vehiclePolicy->forceDelete($this->admin, $this->vehicle))->toBeTrue();
        expect($this->vehiclePolicy->forceDelete($this->owner, $this->vehicle))->toBeFalse();
        expect($this->vehiclePolicy->forceDelete($this->renter, $this->vehicle))->toBeFalse();
    });
});
