<?php

use App\Models\Review;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Review Model', function () {
    beforeEach(function () {
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->booking = Booking::factory()->create([
            'renter_id' => $this->renter->id,
            'vehicle_id' => $this->vehicle->id
        ]);
        
        $this->review = Review::factory()->create([
            'booking_id' => $this->booking->id,
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $this->renter->id,
            'rating' => 5,
            'comment' => 'Excellent vehicle and service!'
        ]);
    });

    it('belongs to a booking', function () {
        expect($this->review->booking)->toBeInstanceOf(Booking::class);
        expect($this->review->booking->id)->toBe($this->booking->id);
    });

    it('belongs to a vehicle', function () {
        expect($this->review->vehicle)->toBeInstanceOf(Vehicle::class);
        expect($this->review->vehicle->id)->toBe($this->vehicle->id);
    });

    it('belongs to a renter', function () {
        expect($this->review->renter)->toBeInstanceOf(User::class);
        expect($this->review->renter->id)->toBe($this->renter->id);
    });

    it('uses correct table name', function () {
        expect($this->review->getTable())->toBe('car_rental_reviews');
    });

    it('has correct fillable attributes', function () {
        $expectedFillable = [
            'booking_id', 'vehicle_id', 'renter_id', 'rating', 'comment',
            'recommendation', 'pros', 'cons', 'cleanliness_rating',
            'comfort_rating', 'value_rating', 'is_visible'
        ];
        
        expect($this->review->getFillable())->toEqual($expectedFillable);
    });

    it('casts attributes correctly', function () {
        expect($this->review->rating)->toBeInt();
        expect($this->review->cleanliness_rating)->toBeInt();
        expect($this->review->comfort_rating)->toBeInt();
        expect($this->review->value_rating)->toBeInt();
        expect($this->review->is_visible)->toBeBool();
    });

    it('validates rating range', function () {
        $validRatings = [1, 2, 3, 4, 5];
        
        foreach ($validRatings as $rating) {
            $review = Review::factory()->create(['rating' => $rating]);
            expect($review->rating)->toBe($rating);
        }
    });

    it('can have detailed ratings', function () {
        $review = Review::factory()->create([
            'rating' => 4,
            'cleanliness_rating' => 5,
            'comfort_rating' => 4,
            'value_rating' => 3
        ]);
        
        expect($review->cleanliness_rating)->toBe(5);
        expect($review->comfort_rating)->toBe(4);
        expect($review->value_rating)->toBe(3);
    });

    it('can have recommendation status', function () {
        $yesReview = Review::factory()->create(['recommendation' => 'yes']);
        $noReview = Review::factory()->create(['recommendation' => 'no']);
        $maybeReview = Review::factory()->create(['recommendation' => 'maybe']);
        
        expect($yesReview->recommendation)->toBe('yes');
        expect($noReview->recommendation)->toBe('no');
        expect($maybeReview->recommendation)->toBe('maybe');
    });

    it('can have pros and cons', function () {
        $review = Review::factory()->create([
            'pros' => 'Clean, comfortable, good fuel efficiency',
            'cons' => 'Slightly noisy engine, small trunk space'
        ]);
        
        expect($review->pros)->toBe('Clean, comfortable, good fuel efficiency');
        expect($review->cons)->toBe('Slightly noisy engine, small trunk space');
    });

    it('can be visible or hidden', function () {
        $visibleReview = Review::factory()->create(['is_visible' => true]);
        $hiddenReview = Review::factory()->create(['is_visible' => false]);
        
        expect($visibleReview->is_visible)->toBeTrue();
        expect($hiddenReview->is_visible)->toBeFalse();
    });

    it('creates reviews with factory states', function () {
        $excellentReview = Review::factory()->excellent()->create();
        $poorReview = Review::factory()->poor()->create();
        
        expect($excellentReview->rating)->toBe(5);
        expect($poorReview->rating)->toBeLessThanOrEqual(2);
    });

    it('can calculate average rating', function () {
        // Create multiple reviews for the same vehicle
        Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 5]);
        Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 4]);
        Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 3]);
        
        $averageRating = Review::where('vehicle_id', $this->vehicle->id)->avg('rating');
        expect($averageRating)->toBeFloat();
        expect($averageRating)->toBeGreaterThan(3);
        expect($averageRating)->toBeLessThanOrEqual(5);
    });

    it('ensures unique booking review constraint', function () {
        // Try to create another review for the same booking
        expect(function () {
            Review::factory()->create([
                'booking_id' => $this->booking->id,
                'vehicle_id' => $this->vehicle->id,
                'renter_id' => $this->renter->id
            ]);
        })->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can filter visible reviews', function () {
        Review::factory()->create(['is_visible' => true]);
        Review::factory()->create(['is_visible' => false]);
        Review::factory()->create(['is_visible' => true]);
        
        $visibleReviews = Review::where('is_visible', true)->get();
        expect($visibleReviews)->toHaveCount(3); // Including the one from beforeEach
    });
});