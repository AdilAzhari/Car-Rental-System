<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\VehicleResource;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to access users resource methods', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // Test UserResource permissions
    expect(UserResource::canCreate())->toBeTrue();
    expect(UserResource::canViewAny())->toBeTrue();
    expect(UserResource::shouldRegisterNavigation())->toBeTrue();
});

it('allows admin to access vehicle actions', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    // Test VehicleResource permissions
    expect(VehicleResource::canCreate())->toBeTrue();
});

it('allows admin to view users resource page', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    $response = $this->get('/admin/users');
    expect($response->status())->toBe(200);
});

it('allows admin to view vehicles with actions available', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($admin);

    $response = $this->get('/admin/vehicles');
    expect($response->status())->toBe(200);
});

it('allows admin to view individual vehicle record', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($admin);

    $response = $this->get("/admin/vehicles/{$vehicle->id}");
    expect($response->status())->toBe(200);
});

it('allows admin to edit vehicle record', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($admin);

    $response = $this->get("/admin/vehicles/{$vehicle->id}/edit");
    expect($response->status())->toBe(200);
});
