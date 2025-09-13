<?php

use App\Models\Booking;
use App\Models\Review;
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

    // Create vehicle and booking
    $this->vehicle = Vehicle::factory()->create([
        'owner_id' => $this->owner->id,
        'status' => 'published',
    ]);

    $this->booking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'status' => 'completed',
    ]);

    // Create sample review
    $this->review = Review::factory()->create([
        'booking_id' => $this->booking->id,
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'rating' => 5,
        'comment' => 'Great vehicle and service!',
        'is_visible' => true,
    ]);
});

it('can list reviews as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->assertSee('Reviews')
        ->assertSee($this->renter->name)
        ->assertSee($this->review->comment)
        ->assertSee('⭐⭐⭐⭐⭐')
        ->assertNoJavascriptErrors();
});

it('can view review details as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->click('View')
        ->assertSee($this->review->comment)
        ->assertSee($this->renter->name)
        ->assertSee($this->vehicle->make)
        ->assertSee('Rating: 5')
        ->assertNoJavascriptErrors();
});

it('can create new review as admin', function (): void {
    // Create another completed booking for testing
    $newBooking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'status' => 'completed',
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->click('New review')
        ->select('data.booking_id', $newBooking->id)
        ->select('data.renter_id', $this->renter->id)
        ->fill('data.rating', '4')
        ->fill('data.comment', 'Good experience overall')
        ->press('Create')
        ->assertSee('Review created successfully')
        ->assertNoJavascriptErrors();

    expect(Review::where('comment', 'Good experience overall')->count())->toBe(1);
});

it('can edit review as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/reviews/{$this->review->id}/edit")
        ->fill('data.comment', 'Updated review comment')
        ->fill('data.rating', '4')
        ->press('Save changes')
        ->assertSee('Review updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->review->refresh()->comment)->toBe('Updated review comment');
    expect($this->review->refresh()->rating)->toBe(4);
});

it('can toggle review visibility as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/reviews/{$this->review->id}/edit")
        ->uncheck('data.is_visible')
        ->press('Save changes')
        ->assertSee('Review updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->review->refresh()->is_visible)->toBeFalse();
});

it('can delete review as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->click('Delete')
        ->press('Delete')
        ->assertSee('Review deleted successfully')
        ->assertNoJavascriptErrors();

    expect(Review::find($this->review->id))->toBeNull();
});

it('can filter reviews by rating', function (): void {
    // Create reviews with different ratings
    Review::factory()->create([
        'booking_id' => $this->booking->id,
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'rating' => 3,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->click('Filters')
        ->select('tableFilters.rating.value', '5')
        ->press('Apply')
        ->assertSee('⭐⭐⭐⭐⭐')
        ->assertNoJavascriptErrors();
});

it('can filter reviews by visibility', function (): void {
    // Create hidden review
    Review::factory()->create([
        'booking_id' => $this->booking->id,
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'is_visible' => false,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->click('Filters')
        ->select('tableFilters.is_visible.value', '0')
        ->press('Apply')
        ->assertSee('Hidden')
        ->assertNoJavascriptErrors();
});

it('can search reviews by comment', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->fill('tableSearch', 'Great vehicle')
        ->assertSee($this->review->comment)
        ->assertNoJavascriptErrors();
});

it('owner can only see reviews for their vehicles', function (): void {
    // Create review for another owner's vehicle
    $otherOwner = User::factory()->owner()->create();
    $otherVehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);
    $otherBooking = Booking::factory()->create([
        'vehicle_id' => $otherVehicle->id,
        'renter_id' => $this->renter->id,
    ]);
    $otherReview = Review::factory()->create([
        'booking_id' => $otherBooking->id,
        'vehicle_id' => $otherVehicle->id,
        'renter_id' => $this->renter->id,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->owner->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->assertSee($this->review->comment) // Should see own vehicle review
        ->assertDontSee($otherReview->comment) // Should not see other's review
        ->assertNoJavascriptErrors();
});

it('renter can only see their own reviews', function (): void {
    // Create review by another renter
    $otherRenter = User::factory()->renter()->create();
    $otherBooking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $otherRenter->id,
    ]);
    $otherReview = Review::factory()->create([
        'booking_id' => $otherBooking->id,
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $otherRenter->id,
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->renter->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/reviews')
        ->assertSee($this->review->comment) // Should see own review
        ->assertDontSee($otherReview->comment) // Should not see other's review
        ->assertNoJavascriptErrors();
});
