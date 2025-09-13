<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin user for testing
    $this->admin = User::factory()->admin()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
    ]);

    // Create sample users
    $this->owner = User::factory()->owner()->create([
        'name' => 'Test Owner',
        'email' => 'owner@test.com',
    ]);

    $this->renter = User::factory()->renter()->create([
        'name' => 'Test Renter',
        'email' => 'renter@test.com',
    ]);
});

it('can list users as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->assertSee('Users')
        ->assertSee($this->owner->name)
        ->assertSee($this->renter->name)
        ->assertSee($this->owner->email)
        ->assertNoJavascriptErrors();
});

it('can view user details as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->click('View')
        ->assertSee($this->owner->name)
        ->assertSee($this->owner->email)
        ->assertSee($this->owner->role->getLabel())
        ->assertNoJavascriptErrors();
});

it('can create new user as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->click('New user')
        ->fill('data.name', 'New Test User')
        ->fill('data.email', 'newuser@test.com')
        ->fill('data.password', 'password123')
        ->fill('data.password_confirmation', 'password123')
        ->select('data.role', 'renter')
        ->press('Create')
        ->assertSee('User created successfully')
        ->assertNoJavascriptErrors();

    expect(User::where('email', 'newuser@test.com')->count())->toBe(1);
});

it('can edit user as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/users/{$this->owner->id}/edit")
        ->fill('data.name', 'Updated Owner Name')
        ->fill('data.phone', '+1234567890')
        ->press('Save changes')
        ->assertSee('User updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->owner->refresh()->name)->toBe('Updated Owner Name');
    expect($this->owner->refresh()->phone)->toBe('+1234567890');
});

it('can change user role as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/users/{$this->renter->id}/edit")
        ->select('data.role', 'owner')
        ->press('Save changes')
        ->assertSee('User updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->renter->refresh()->role->value)->toBe('owner');
});

it('can delete user as admin', function (): void {
    $userToDelete = User::factory()->renter()->create();

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->click('Delete')
        ->press('Delete')
        ->assertSee('User deleted successfully')
        ->assertNoJavascriptErrors();

    expect(User::find($userToDelete->id))->toBeNull();
});

it('can filter users by role', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->click('Filters')
        ->select('tableFilters.role.value', 'owner')
        ->press('Apply')
        ->assertSee($this->owner->name)
        ->assertDontSee($this->renter->name)
        ->assertNoJavascriptErrors();
});

it('can search users by name', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->fill('tableSearch', $this->owner->name)
        ->assertSee($this->owner->name)
        ->assertNoJavascriptErrors();
});

it('can search users by email', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->fill('tableSearch', $this->owner->email)
        ->assertSee($this->owner->email)
        ->assertNoJavascriptErrors();
});

it('can bulk delete users as admin', function (): void {
    $user1 = User::factory()->renter()->create();
    $user2 = User::factory()->owner()->create();

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->check('recordCheckbox.0')
        ->check('recordCheckbox.1')
        ->click('Delete selected')
        ->press('Delete')
        ->assertSee('Users deleted successfully')
        ->assertNoJavascriptErrors();
});

it('shows user statistics correctly', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/users')
        ->assertSee('Total Users')
        ->assertSee('Recent Registrations')
        ->assertNoJavascriptErrors();
});
