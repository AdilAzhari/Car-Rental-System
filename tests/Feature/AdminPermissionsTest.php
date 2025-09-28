<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\VehicleResource;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use App\Filament\Resources\VehicleResource\Pages\EditVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows admin to access users resource methods', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    // Test UserResource permissions
    expect(UserResource::canCreate())->toBeTrue()
        ->and(UserResource::canViewAny())->toBeTrue()
        ->and(UserResource::shouldRegisterNavigation())->toBeTrue();
});

it('allows admin to access vehicle actions', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin);

    // Test VehicleResource permissions
    expect(VehicleResource::canCreate())->toBeTrue();
});

it('allows admin to view users list in Filament', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $users = User::factory(3)->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($users);
});

it('allows admin to view vehicles list in Filament', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $vehicles = Vehicle::factory(3)->create();

    $this->actingAs($admin);

    Livewire::test(ListVehicles::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($vehicles);
});

it('allows admin to edit vehicle in Filament', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditVehicle::class, ['record' => $vehicle->getRouteKey()])
        ->assertSuccessful()
        ->assertFormSet([
            'make' => $vehicle->make,
            'model' => $vehicle->model,
        ]);
});

it('allows admin to edit user in Filament', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertSuccessful()
        ->assertFormSet([
            'name' => $user->name,
            'email' => $user->email,
        ]);
});
