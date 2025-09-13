<?php

use App\Models\User;

describe('Vehicle Creation Permissions', function (): void {

    test('admin can create vehicles')
        ->expect(function (): true {
            $admin = User::factory()->create(['role' => 'admin']);

            $this->actingAs($admin);

            $response = $this->get('/admin/vehicles/create');
            expect($response->status())->toBe(200);

            return true;
        })->toBeTrue();

    test('owner can create vehicles')
        ->expect(function (): true {
            $owner = User::factory()->create(['role' => 'owner']);

            $this->actingAs($owner);

            $response = $this->get('/admin/vehicles/create');
            expect($response->status())->toBe(200);

            return true;
        })->toBeTrue();

    test('renter cannot create vehicles')
        ->expect(function (): true {
            $renter = User::factory()->create(['role' => 'renter']);

            $this->actingAs($renter);

            $response = $this->get('/admin/vehicles/create');
            expect($response->status())->toBe(403);

            return true;
        })->toBeTrue();

    test('guest cannot create vehicles')
        ->expect(function (): true {
            $response = $this->get('/admin/vehicles/create');
            expect($response->status())->toBe(302); // Redirect to login

            return true;
        })->toBeTrue();

    test('vehicle resource canCreate method works correctly')
        ->expect(function (): true {
            // Test admin
            $admin = User::factory()->create(['role' => 'admin']);
            $this->actingAs($admin);
            expect(\App\Filament\Resources\VehicleResource::canCreate())->toBeTrue();

            // Test owner
            $owner = User::factory()->create(['role' => 'owner']);
            $this->actingAs($owner);
            expect(\App\Filament\Resources\VehicleResource::canCreate())->toBeTrue();

            // Test renter
            $renter = User::factory()->create(['role' => 'renter']);
            $this->actingAs($renter);
            expect(\App\Filament\Resources\VehicleResource::canCreate())->toBeFalse();

            return true;
        })->toBeTrue();

});
