<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Review;
use App\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

describe('Review Management', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->completedBooking = Booking::factory()->create([
            'renter_id' => $this->renter->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => BookingStatus::COMPLETED,
            'end_date' => Carbon::yesterday()
        ]);
    });

    describe('Review Listing', function () {
        it('allows admin to view all reviews', function () {
            Review::factory(3)->create(['vehicle_id' => $this->vehicle->id]);
            
            $this->actingAs($this->admin)
                ->get('/admin/reviews')
                ->assertSuccessful()
                ->assertSee('Reviews');
        });

        it('allows owner to view reviews for their vehicles', function () {
            Review::factory(2)->create(['vehicle_id' => $this->vehicle->id]);
            
            $this->actingAs($this->owner)
                ->get('/admin/reviews')
                ->assertSuccessful();
        });

        it('allows renter to view their own reviews', function () {
            Review::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'booking_id' => $this->completedBooking->id
            ]);
            
            $this->actingAs($this->renter)
                ->get('/admin/reviews')
                ->assertSuccessful();
        });

        it('filters reviews by rating', function () {
            Review::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'rating' => 5
            ]);
            
            $this->actingAs($this->admin)
                ->get('/admin/reviews?rating=5')
                ->assertSuccessful();
        });

        it('filters reviews by vehicle', function () {
            Review::factory()->create(['vehicle_id' => $this->vehicle->id]);
            
            $this->actingAs($this->admin)
                ->get("/admin/reviews?vehicle_id={$this->vehicle->id}")
                ->assertSuccessful();
        });
    });

    describe('Review Creation', function () {
        it('allows renter to create review after completed booking', function () {
            $reviewData = [
                'booking_id' => $this->completedBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 5,
                'comment' => 'Excellent vehicle and service!',
                'cleanliness_rating' => 5,
                'comfort_rating' => 4,
                'value_rating' => 5,
                'recommendation' => 'yes'
            ];
            
            $this->actingAs($this->renter)
                ->post('/admin/reviews', $reviewData);
            
            $review = Review::where('booking_id', $this->completedBooking->id)->first();
            expect($review->rating)->toBe(5);
            expect($review->recommendation)->toBe('yes');
        });

        it('prevents review creation for non-completed bookings', function () {
            $activeBooking = Booking::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => BookingStatus::CONFIRMED
            ]);
            
            $reviewData = [
                'booking_id' => $activeBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 5,
                'comment' => 'Great experience!'
            ];
            
            $this->actingAs($this->renter)
                ->post('/admin/reviews', $reviewData)
                ->assertSessionHasErrors();
        });

        it('prevents duplicate reviews for same booking', function () {
            Review::factory()->create([
                'booking_id' => $this->completedBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'renter_id' => $this->renter->id
            ]);
            
            $reviewData = [
                'booking_id' => $this->completedBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 4,
                'comment' => 'Another review'
            ];
            
            $this->actingAs($this->renter)
                ->post('/admin/reviews', $reviewData)
                ->assertSessionHasErrors();
        });

        it('validates rating range', function () {
            $reviewData = [
                'booking_id' => $this->completedBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 6, // Invalid rating
                'comment' => 'Good service'
            ];
            
            $this->actingAs($this->renter)
                ->post('/admin/reviews', $reviewData)
                ->assertSessionHasErrors(['rating']);
        });
    });

    describe('Review Updates', function () {
        beforeEach(function () {
            $this->review = Review::factory()->create([
                'booking_id' => $this->completedBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'renter_id' => $this->renter->id,
                'rating' => 4
            ]);
        });

        it('allows renter to update their own review', function () {
            $updateData = [
                'rating' => 5,
                'comment' => 'Updated: Even better than I thought!'
            ];
            
            $this->actingAs($this->renter)
                ->patch("/admin/reviews/{$this->review->id}", $updateData);
            
            $this->review->refresh();
            expect($this->review->rating)->toBe(5);
            expect($this->review->comment)->toContain('Updated:');
        });

        it('prevents renter from updating other renters reviews', function () {
            $otherRenter = User::factory()->renter()->create();
            $updateData = ['rating' => 1];
            
            $this->actingAs($otherRenter)
                ->patch("/admin/reviews/{$this->review->id}", $updateData)
                ->assertForbidden();
        });

        it('allows admin to update any review', function () {
            $updateData = ['rating' => 3];
            
            $this->actingAs($this->admin)
                ->patch("/admin/reviews/{$this->review->id}", $updateData);
            
            $this->review->refresh();
            expect($this->review->rating)->toBe(3);
        });
    });

    describe('Review Moderation', function () {
        beforeEach(function () {
            $this->review = Review::factory()->create([
                'vehicle_id' => $this->vehicle->id,
                'is_visible' => true
            ]);
        });

        it('allows admin to hide inappropriate reviews', function () {
            $this->actingAs($this->admin)
                ->patch("/admin/reviews/{$this->review->id}", [
                    'is_visible' => false
                ]);
            
            $this->review->refresh();
            expect($this->review->is_visible)->toBeFalse();
        });

        it('allows admin to approve hidden reviews', function () {
            $this->review->update(['is_visible' => false]);
            
            $this->actingAs($this->admin)
                ->patch("/admin/reviews/{$this->review->id}", [
                    'is_visible' => true
                ]);
            
            $this->review->refresh();
            expect($this->review->is_visible)->toBeTrue();
        });

        it('filters visible reviews in public views', function () {
            Review::factory()->create(['is_visible' => true]);
            Review::factory()->create(['is_visible' => false]);
            
            $this->get("/vehicles/{$this->vehicle->id}/reviews")
                ->assertSuccessful();
        });
    });

    describe('Review Analytics', function () {
        it('calculates average rating for vehicle', function () {
            Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 5]);
            Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 4]);
            Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 3]);
            
            $this->actingAs($this->admin)
                ->get("/admin/vehicles/{$this->vehicle->id}/reviews-analytics")
                ->assertSuccessful();
        });

        it('displays rating distribution', function () {
            Review::factory(2)->create(['vehicle_id' => $this->vehicle->id, 'rating' => 5]);
            Review::factory(3)->create(['vehicle_id' => $this->vehicle->id, 'rating' => 4]);
            Review::factory(1)->create(['vehicle_id' => $this->vehicle->id, 'rating' => 3]);
            
            $this->actingAs($this->admin)
                ->get("/admin/reviews/analytics")
                ->assertSuccessful();
        });
    });

    describe('Review with Detailed Ratings', function () {
        it('creates review with all detailed ratings', function () {
            $reviewData = [
                'booking_id' => $this->completedBooking->id,
                'vehicle_id' => $this->vehicle->id,
                'rating' => 4,
                'cleanliness_rating' => 5,
                'comfort_rating' => 4,
                'value_rating' => 3,
                'comment' => 'Good overall experience',
                'pros' => 'Clean and comfortable',
                'cons' => 'Slightly expensive',
                'recommendation' => 'yes'
            ];
            
            $this->actingAs($this->renter)
                ->post('/admin/reviews', $reviewData);
            
            $review = Review::where('booking_id', $this->completedBooking->id)->first();
            expect($review->cleanliness_rating)->toBe(5);
            expect($review->comfort_rating)->toBe(4);
            expect($review->value_rating)->toBe(3);
            expect($review->pros)->toBe('Clean and comfortable');
            expect($review->cons)->toBe('Slightly expensive');
        });
    });

    describe('Review Deletion', function () {
        beforeEach(function () {
            $this->review = Review::factory()->create([
                'renter_id' => $this->renter->id,
                'vehicle_id' => $this->vehicle->id
            ]);
        });

        it('allows admin to delete any review', function () {
            $this->actingAs($this->admin)
                ->delete("/admin/reviews/{$this->review->id}");
            
            expect(Review::find($this->review->id))->toBeNull();
        });

        it('allows renter to delete their own review', function () {
            $this->actingAs($this->renter)
                ->delete("/admin/reviews/{$this->review->id}");
            
            expect(Review::find($this->review->id))->toBeNull();
        });

        it('prevents renter from deleting other renters reviews', function () {
            $otherRenter = User::factory()->renter()->create();
            
            $this->actingAs($otherRenter)
                ->delete("/admin/reviews/{$this->review->id}")
                ->assertForbidden();
        });
    });
});