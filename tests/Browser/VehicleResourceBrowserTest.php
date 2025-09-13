<?php

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin user for testing
    $this->admin = User::factory()->admin()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
    ]);

    // Create owner user for testing
    $this->owner = User::factory()->owner()->create([
        'name' => 'Test Owner',
        'email' => 'owner@test.com',
    ]);

    // Create sample vehicle
    $this->vehicle = Vehicle::factory()->create([
        'owner_id' => $this->owner->id,
        'make' => 'Toyota',
        'model' => 'Camry',
        'year' => 2023,
        'status' => 'published',
    ]);
});

it('can list vehicles as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->assertAuthenticated()
        ->visit('/admin/vehicles')
        ->assertSee('Vehicles')
        ->assertSee($this->vehicle->make)
        ->assertSee($this->vehicle->model)
        ->assertNoJavascriptErrors();
});

it('can view vehicle details as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->click('View')
        ->assertSee($this->vehicle->make)
        ->assertSee($this->vehicle->model)
        ->assertSee($this->vehicle->year)
        ->assertSee($this->vehicle->status->getLabel())
        ->assertNoJavascriptErrors();
});

it('can create new vehicle as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->click('New vehicle')
        ->fill('data.make', 'Honda')
        ->fill('data.model', 'Civic')
        ->fill('data.year', '2023')
        ->fill('data.plate_number', 'ABC-1234')
        ->fill('data.daily_rate', '50')
        ->select('data.fuel_type', 'petrol')
        ->select('data.transmission', 'automatic')
        ->select('data.owner_id', $this->owner->id)
        ->press('Create')
        ->assertSee('Vehicle created successfully')
        ->assertNoJavascriptErrors();

    expect(Vehicle::where('make', 'Honda')->where('model', 'Civic')->count())->toBe(1);
});

it('can edit vehicle as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/vehicles/{$this->vehicle->id}/edit")
        ->fill('data.make', 'Updated Toyota')
        ->fill('data.model', 'Updated Camry')
        ->press('Save changes')
        ->assertSee('Vehicle updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->vehicle->refresh()->make)->toBe('Updated Toyota');
    expect($this->vehicle->refresh()->model)->toBe('Updated Camry');
});

it('can delete vehicle as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->click('Delete')
        ->press('Delete')
        ->assertSee('Vehicle deleted successfully')
        ->assertNoJavascriptErrors();

    expect(Vehicle::find($this->vehicle->id))->toBeNull();
});

it('can filter vehicles by status', function (): void {
    // Create vehicles with different statuses
    Vehicle::factory()->create(['status' => 'pending', 'owner_id' => $this->owner->id]);
    Vehicle::factory()->create(['status' => 'approved', 'owner_id' => $this->owner->id]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->click('Filters')
        ->select('tableFilters.status.value', 'pending')
        ->press('Apply')
        ->assertSee('pending')
        ->assertNoJavascriptErrors();
});

it('can search vehicles by make', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->fill('tableSearch', 'Toyota')
        ->assertSee($this->vehicle->make)
        ->assertNoJavascriptErrors();
});

it('owner can only see their own vehicles', function (): void {
    // Create vehicle owned by different owner
    $otherOwner = User::factory()->owner()->create();
    $otherVehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);

    $page = visit('/admin/login')
        ->fill('email', $this->owner->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->assertSee($this->vehicle->make) // Should see own vehicle
        ->assertDontSee($otherVehicle->make) // Should not see other's vehicle
        ->assertNoJavascriptErrors();
});

it('can bulk delete vehicles as admin', function (): void {
    $vehicle2 = Vehicle::factory()->create(['owner_id' => $this->owner->id]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/vehicles')
        ->check('recordCheckbox.0')
        ->check('recordCheckbox.1')
        ->click('Delete selected')
        ->press('Delete')
        ->assertSee('Vehicles deleted successfully')
        ->assertNoJavascriptErrors();

    expect(Vehicle::count())->toBe(0);
});
