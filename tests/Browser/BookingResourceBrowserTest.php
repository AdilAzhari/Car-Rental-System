<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create users
    $this->admin = User::factory()->admin()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
    ]);

    $this->owner = User::factory()->owner()->create([
        'name' => 'Test Owner',
        'email' => 'owner@test.com',
    ]);

    $this->renter = User::factory()->renter()->create([
        'name' => 'Test Renter',
        'email' => 'renter@test.com',
    ]);

    // Create vehicle
    $this->vehicle = Vehicle::factory()->create([
        'owner_id' => $this->owner->id,
        'status' => 'published',
    ]);

    // Create sample booking
    $this->booking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'status' => 'confirmed',
    ]);
});

it('can list bookings as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->assertSee('Bookings')
        ->assertSee($this->booking->booking_reference)
        ->assertSee($this->renter->name)
        ->assertNoJavascriptErrors();
});

it('can view booking details as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->click('View')
        ->assertSee($this->booking->booking_reference)
        ->assertSee($this->renter->name)
        ->assertSee($this->vehicle->make)
        ->assertSee($this->booking->status->getLabel())
        ->assertNoJavascriptErrors();
});

it('can create new booking as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->click('New booking')
        ->select('data.vehicle_id', $this->vehicle->id)
        ->select('data.renter_id', $this->renter->id)
        ->fill('data.start_date', now()->addWeek()->format('Y-m-d'))
        ->fill('data.end_date', now()->addWeek()->addDays(2)->format('Y-m-d'))
        ->fill('data.notes', 'Test booking notes')
        ->press('Create')
        ->assertSee('Booking created successfully')
        ->assertNoJavascriptErrors();

    expect(Booking::where('notes', 'Test booking notes')->count())->toBe(1);
});

it('can edit booking as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/bookings/{$this->booking->id}/edit")
        ->fill('data.notes', 'Updated booking notes')
        ->select('data.status', 'ongoing')
        ->press('Save changes')
        ->assertSee('Booking updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->booking->refresh()->notes)->toBe('Updated booking notes');
});

it('can cancel booking as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/bookings/{$this->booking->id}/edit")
        ->select('data.status', 'cancelled')
        ->press('Save changes')
        ->assertSee('Booking updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->booking->refresh()->status->value)->toBe('cancelled');
});

it('can filter bookings by status', function (): void {
    // Create bookings with different statuses
    Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'status' => 'pending',
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->click('Filters')
        ->select('tableFilters.status.value', 'pending')
        ->press('Apply')
        ->assertSee('pending')
        ->assertNoJavascriptErrors();
});

it('can search bookings by reference', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->fill('tableSearch', $this->booking->booking_reference)
        ->assertSee($this->booking->booking_reference)
        ->assertNoJavascriptErrors();
});

it('owner can only see bookings for their vehicles', function (): void {
    // Create booking for another owner's vehicle
    $otherOwner = User::factory()->owner()->create();
    $otherVehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);
    $otherBooking = Booking::factory()->create([
        'vehicle_id' => $otherVehicle->id,
        'renter_id' => $this->renter->id,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->owner->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->assertSee($this->booking->booking_reference) // Should see own vehicle booking
        ->assertDontSee($otherBooking->booking_reference) // Should not see other's booking
        ->assertNoJavascriptErrors();
});

it('renter can only see their own bookings', function (): void {
    // Create booking for another renter
    $otherRenter = User::factory()->renter()->create();
    $otherBooking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $otherRenter->id,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->renter->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/bookings')
        ->assertSee($this->booking->booking_reference) // Should see own booking
        ->assertDontSee($otherBooking->booking_reference) // Should not see other's booking
        ->assertNoJavascriptErrors();
});
