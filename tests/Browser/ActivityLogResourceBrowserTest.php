<?php

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create admin user for testing
    $this->admin = User::factory()->admin()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
    ]);

    $this->owner = User::factory()->owner()->create([
        'name' => 'Test Owner',
        'email' => 'owner@test.com',
    ]);

    // Create vehicle to generate activity logs
    $this->vehicle = Vehicle::factory()->create([
        'owner_id' => $this->owner->id,
        'make' => 'Toyota',
        'model' => 'Camry',
    ]);

    // Create some activity logs
    $this->activity = Activity::create([
        'log_name' => 'vehicle',
        'description' => 'Vehicle was created',
        'subject_type' => Vehicle::class,
        'subject_id' => $this->vehicle->id,
        'causer_type' => User::class,
        'causer_id' => $this->owner->id,
        'properties' => [
            'attributes' => [
                'make' => 'Toyota',
                'model' => 'Camry',
                'status' => 'pending',
            ],
        ],
    ]);
});

it('can list activity logs as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->assertSee('Activity Logs')
        ->assertSee('Vehicle was created')
        ->assertSee($this->owner->name)
        ->assertSee('vehicle')
        ->assertNoJavascriptErrors();
});

it('can view activity log details as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->click('View')
        ->assertSee('Vehicle was created')
        ->assertSee($this->owner->name)
        ->assertSee('Toyota')
        ->assertSee('Camry')
        ->assertNoJavascriptErrors();
});

it('can filter activity logs by log name', function (): void {
    // Create different types of activities
    Activity::create([
        'log_name' => 'user',
        'description' => 'User was updated',
        'subject_type' => User::class,
        'subject_id' => $this->owner->id,
        'causer_type' => User::class,
        'causer_id' => $this->admin->id,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->click('Filters')
        ->select('tableFilters.log_name.value', 'vehicle')
        ->press('Apply')
        ->assertSee('Vehicle was created')
        ->assertDontSee('User was updated')
        ->assertNoJavascriptErrors();
});

it('can filter activity logs by causer', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->click('Filters')
        ->select('tableFilters.causer_id.value', $this->owner->id)
        ->press('Apply')
        ->assertSee($this->owner->name)
        ->assertNoJavascriptErrors();
});

it('can search activity logs by description', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->fill('tableSearch', 'Vehicle was created')
        ->assertSee('Vehicle was created')
        ->assertNoJavascriptErrors();
});

it('shows activity logs in chronological order', function (): void {
    // Create additional activities
    Activity::create([
        'log_name' => 'vehicle',
        'description' => 'Vehicle was updated',
        'subject_type' => Vehicle::class,
        'subject_id' => $this->vehicle->id,
        'causer_type' => User::class,
        'causer_id' => $this->admin->id,
        'created_at' => now()->addMinute(),
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->assertSeeInOrder(['Vehicle was updated', 'Vehicle was created'])
        ->assertNoJavascriptErrors();
});

it('can delete activity logs as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->click('Delete')
        ->press('Delete')
        ->assertSee('Activity log deleted successfully')
        ->assertNoJavascriptErrors();

    expect(Activity::find($this->activity->id))->toBeNull();
});

it('can bulk delete activity logs as admin', function (): void {
    $activity2 = Activity::create([
        'log_name' => 'vehicle',
        'description' => 'Another activity',
        'subject_type' => Vehicle::class,
        'subject_id' => $this->vehicle->id,
        'causer_type' => User::class,
        'causer_id' => $this->owner->id,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->check('recordCheckbox.0')
        ->check('recordCheckbox.1')
        ->click('Delete selected')
        ->press('Delete')
        ->assertSee('Activity logs deleted successfully')
        ->assertNoJavascriptErrors();

    expect(Activity::count())->toBe(0);
});

it('shows proper activity log properties', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->click('View')
        ->assertSee('Properties')
        ->assertSee('make')
        ->assertSee('Toyota')
        ->assertSee('model')
        ->assertSee('Camry')
        ->assertNoJavascriptErrors();
});

it('can export activity logs as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->click('Export')
        ->click('Export as CSV')
        ->assertNoJavascriptErrors();
});

it('shows activity statistics correctly', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/activity-logs')
        ->assertSee('Total Activities')
        ->assertSee('Recent Activities')
        ->assertNoJavascriptErrors();
});
