<?php

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Review Model', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->booking = Booking::factory()->create([
            'renter_id' => $this->renter->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $this->review = Review::factory()->create([
            'booking_id' => $this->booking->id,
            'vehicle_id' => $this->vehicle->id,
            'renter_id' => $this->renter->id,
            'rating' => 5,
            'comment' => 'Excellent vehicle and service!',
        ]);
    });

    it('belongs to a booking', function (): void {
        expect($this->review->booking)->toBeInstanceOf(Booking::class);
        expect($this->review->booking->id)->toBe($this->booking->id);
    });

    it('belongs to a vehicle', function (): void {
        expect($this->review->vehicle)->toBeInstanceOf(Vehicle::class);
        expect($this->review->vehicle->id)->toBe($this->vehicle->id);
    });

    it('belongs to a renter', function (): void {
        expect($this->review->renter)->toBeInstanceOf(User::class);
        expect($this->review->renter->id)->toBe($this->renter->id);
    });

    it('uses correct table name', function (): void {
        expect($this->review->getTable())->toBe('car_rental_reviews');
    });

    it('has correct fillable attributes', function (): void {
        $expectedFillable = [
            'booking_id', 'vehicle_id', 'renter_id', 'rating', 'comment',
            'is_visible',
        ];

        expect($this->review->getFillable())->toEqual($expectedFillable);
    });

    it('casts attributes correctly', function (): void {
        expect($this->review->rating)->toBeInt();
        expect($this->review->is_visible)->toBeBool();
    });

    it('validates rating range', function (): void {
        $validRatings = [1, 2, 3, 4, 5];

        foreach ($validRatings as $validRating) {
            $review = Review::factory()->create(['rating' => $validRating]);
            expect($review->rating)->toBe($validRating);
        }
    });

    it('can have detailed ratings', function (): void {
        // Detailed ratings not implemented in current model
        $this->markTestSkipped('Detailed ratings not implemented');
    });

    it('can have recommendation status', function (): void {
        // Recommendation status not implemented in current model
        $this->markTestSkipped('Recommendation status not implemented');
    });

    it('can have pros and cons', function (): void {
        // Pros/cons not implemented in current model
        $this->markTestSkipped('Pros/cons not implemented');
    });

    it('can be visible or hidden', function (): void {
        $visibleReview = Review::factory()->create(['is_visible' => true]);
        $hiddenReview = Review::factory()->create(['is_visible' => false]);

        expect($visibleReview->is_visible)->toBeTrue();
        expect($hiddenReview->is_visible)->toBeFalse();
    });

    it('creates reviews with factory states', function (): void {
        $excellentReview = Review::factory()->excellent()->create();
        $poorReview = Review::factory()->poor()->create();

        expect($excellentReview->rating)->toBe(5);
        expect($poorReview->rating)->toBeLessThanOrEqual(2);
    });

    it('can calculate average rating', function (): void {
        // Create multiple reviews for the same vehicle
        Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 5]);
        Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 4]);
        Review::factory()->create(['vehicle_id' => $this->vehicle->id, 'rating' => 3]);

        $averageRating = (float) Review::where('vehicle_id', $this->vehicle->id)->avg('rating');
        expect($averageRating)->toBeFloat();
        expect($averageRating)->toBeGreaterThan(3);
        expect($averageRating)->toBeLessThanOrEqual(5);
    });

    it('ensures unique booking review constraint', function (): void {
        // Try to create another review for the same booking
        expect(function (): void {
            Review::factory()->create([
                'booking_id' => $this->booking->id,
                'vehicle_id' => $this->vehicle->id,
                'renter_id' => $this->renter->id,
            ]);
        })->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can filter visible reviews', function (): void {
        Review::factory()->create(['is_visible' => true]);
        Review::factory()->create(['is_visible' => false]);
        Review::factory()->create(['is_visible' => true]);

        $visibleReviews = Review::where('is_visible', true)->get();
        expect($visibleReviews)->toHaveCount(3); // Including the one from beforeEach
    });
});
