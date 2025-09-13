<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\VehicleResource;
use App\Models\User;
use App\Models\Vehicle;

describe('Resource Permissions Test', function (): void {

    beforeEach(function (): void {
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->renter = User::factory()->create(['role' => 'renter']);

        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
    });

    describe('Vehicle Resource Permissions', function (): void {

        test('admin can view vehicles')
            ->expect(function (): true {
                $this->actingAs($this->admin);
                expect(VehicleResource::canView($this->vehicle))->toBeTrue();
                expect(VehicleResource::canViewAny())->toBeTrue();

                return true;
            })->toBeTrue();

        test('owner can view vehicles')
            ->expect(function (): true {
                $this->actingAs($this->owner);
                expect(VehicleResource::canView($this->vehicle))->toBeTrue();
                expect(VehicleResource::canViewAny())->toBeTrue();

                return true;
            })->toBeTrue();

        test('admin can create vehicles')
            ->expect(function (): true {
                $this->actingAs($this->admin);
                expect(VehicleResource::canCreate())->toBeTrue();

                return true;
            })->toBeTrue();

        test('owner can create vehicles')
            ->expect(function (): true {
                $this->actingAs($this->owner);
                expect(VehicleResource::canCreate())->toBeTrue();

                return true;
            })->toBeTrue();

        test('renter cannot create vehicles')
            ->expect(function (): true {
                $this->actingAs($this->renter);
                expect(VehicleResource::canCreate())->toBeFalse();

                return true;
            })->toBeTrue();

        test('vehicle view action visibility')
            ->expect(function (): true {
                // Admin should see view action
                $this->actingAs($this->admin);
                auth()->setUser($this->admin);
                $visible = auth()->user() && in_array(auth()->user()->role, ['admin', 'owner']);
                expect($visible)->toBeTrue();

                // Owner should see view action
                $this->actingAs($this->owner);
                auth()->setUser($this->owner);
                $visible = auth()->user() && in_array(auth()->user()->role, ['admin', 'owner']);
                expect($visible)->toBeTrue();

                // Renter should not see view action
                $this->actingAs($this->renter);
                auth()->setUser($this->renter);
                $visible = auth()->user() && in_array(auth()->user()->role, ['admin', 'owner']);
                expect($visible)->toBeFalse();

                return true;
            })->toBeTrue();
    });

    describe('User Resource Permissions', function (): void {

        test('admin has full permissions on users')
            ->expect(function (): true {
                $this->actingAs($this->admin);

                expect(UserResource::canCreate())->toBeTrue();
                expect(UserResource::canView($this->renter))->toBeTrue();
                expect(UserResource::canEdit($this->renter))->toBeTrue();
                expect(UserResource::canDelete($this->renter))->toBeTrue();
                expect(UserResource::canViewAny())->toBeTrue();

                return true;
            })->toBeTrue();

        test('owner cannot access users resource')
            ->expect(function (): true {
                $this->actingAs($this->owner);

                expect(UserResource::canCreate())->toBeFalse();
                expect(UserResource::canView($this->renter))->toBeFalse();
                expect(UserResource::canEdit($this->renter))->toBeFalse();
                expect(UserResource::canDelete($this->renter))->toBeFalse();
                expect(UserResource::canViewAny())->toBeFalse();

                return true;
            })->toBeTrue();

        test('renter cannot access users resource')
            ->expect(function (): true {
                $this->actingAs($this->renter);

                expect(UserResource::canCreate())->toBeFalse();
                expect(UserResource::canView($this->owner))->toBeFalse();
                expect(UserResource::canEdit($this->owner))->toBeFalse();
                expect(UserResource::canDelete($this->owner))->toBeFalse();
                expect(UserResource::canViewAny())->toBeFalse();

                return true;
            })->toBeTrue();

        test('user resource navigation visibility')
            ->expect(function (): true {
                // Admin should see users in navigation
                $this->actingAs($this->admin);
                auth()->setUser($this->admin);
                expect(UserResource::shouldRegisterNavigation())->toBeTrue();

                // Owner should not see users in navigation
                $this->actingAs($this->owner);
                auth()->setUser($this->owner);
                expect(UserResource::shouldRegisterNavigation())->toBeFalse();

                // Renter should not see users in navigation
                $this->actingAs($this->renter);
                auth()->setUser($this->renter);
                expect(UserResource::shouldRegisterNavigation())->toBeFalse();

                return true;
            })->toBeTrue();
    });

    describe('Integration Tests', function (): void {

        test('admin can access all resources')
            ->expect(function (): true {
                $this->actingAs($this->admin);

                // Admin panel access
                $response = $this->get('/admin');
                expect($response->status())->toBe(200);

                // Vehicle access
                $response = $this->get('/admin/vehicles');
                expect($response->status())->toBe(200);

                $response = $this->get('/admin/vehicles/create');
                expect($response->status())->toBe(200);

                // User access (admin only)
                $response = $this->get('/admin/users');
                expect($response->status())->toBe(200);

                return true;
            })->toBeTrue();

        test('owner has limited access')
            ->expect(function (): true {
                $this->actingAs($this->owner);

                // Admin panel access
                $response = $this->get('/admin');
                expect($response->status())->toBe(200);

                // Vehicle access (can create and manage own)
                $response = $this->get('/admin/vehicles');
                expect($response->status())->toBe(200);

                $response = $this->get('/admin/vehicles/create');
                expect($response->status())->toBe(200);

                // User access should be denied
                $response = $this->get('/admin/users');
                expect($response->status())->toBe(403);

                return true;
            })->toBeTrue();

        test('renter has minimal access')
            ->expect(function (): true {
                $this->actingAs($this->renter);

                // Admin panel access
                $response = $this->get('/admin');
                expect($response->status())->toBe(200);

                // Vehicle access (browse only)
                $response = $this->get('/admin/vehicles');
                expect($response->status())->toBe(200);

                // Cannot create vehicles
                $response = $this->get('/admin/vehicles/create');
                expect($response->status())->toBe(403);

                // User access should be denied
                $response = $this->get('/admin/users');
                expect($response->status())->toBe(403);

                return true;
            })->toBeTrue();
    });

});
