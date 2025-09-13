<?php

namespace App\Models;

use App\Enums\VehicleFuelType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleTransmission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehicle extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'car_rental_vehicles';

    protected $fillable = [
        'owner_id',
        'make',
        'model',
        'year',
        'color',
        'plate_number',
        'vin',
        'fuel_type',
        'transmission',
        'seats',
        'daily_rate',
        'description',
        'status',
        'is_available',
        'location',
        'mileage',
        'insurance_expiry',
        'oil_type',
        'last_oil_change',
        'policy',
        'category',
        'doors',
        'engine_size',
        'pickup_location',
        'insurance_included',
        'featured_image',
        'gallery_images',
        'documents',
        'features',
        'terms_and_conditions',
        'traffic_violations',
        'violations_last_checked',
        'total_violations_count',
        'total_fines_amount',
        'has_pending_violations',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'year' => 'integer',
            'seats' => 'integer',
            'doors' => 'integer',
            'mileage' => 'integer',
            'engine_size' => 'decimal:1',
            'last_oil_change' => 'date',
            'insurance_expiry' => 'date',
            'fuel_type' => VehicleFuelType::class,
            'transmission' => VehicleTransmission::class,
            'status' => VehicleStatus::class,
            'is_available' => 'boolean',
            'insurance_included' => 'boolean',
            'gallery_images' => 'array',
            'documents' => 'array',
            'features' => 'array',
            'traffic_violations' => 'array',
            'violations_last_checked' => 'datetime',
            'total_violations_count' => 'integer',
            'total_fines_amount' => 'decimal:2',
            'has_pending_violations' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function vehicleImages(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // === ELOQUENT SCOPES FOR COMPLEX QUERIES ===

    /**
     * Scope vehicles for specific user role with dynamic permissions
     */
    public function scopeForRole($query, $role, $userId = null)
    {
        $userId ??= auth()->id();

        return match ($role) {
            'admin' => $query, // Admins see everything
            'owner' => $query->where('owner_id', $userId),
            'renter' => $query->where('status', 'published')->where('is_available', true),
            default => $query->whereRaw('1 = 0') // No access for unknown roles
        };
    }

    /**
     * Scope for available vehicles with smart filtering
     */
    public function scopeAvailableForRent($query, $startDate = null, $endDate = null)
    {
        $query->where('status', 'published')
            ->where('is_available', true)
            ->where('insurance_expiry', '>', now());

        // Check for booking conflicts if dates provided
        if ($startDate && $endDate) {
            $query->whereDoesntHave('bookings', function ($bookingQuery) use ($startDate, $endDate): void {
                $bookingQuery->where('status', '!=', 'cancelled')
                    ->where(function ($dateQuery) use ($startDate, $endDate): void {
                        $dateQuery->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($overlapQuery) use ($startDate, $endDate): void {
                                $overlapQuery->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
            });
        }

        return $query;
    }

    /**
     * Scope for advanced search with multiple criteria
     */
    public function scopeAdvancedSearch($query, array $filters)
    {
        return $query->when($filters['make'] ?? null, fn ($q, $make) => $q->where('make', 'like', "%$make%"))
            ->when($filters['model'] ?? null, fn ($q, $model) => $q->where('model', 'like', "%{$model}%"))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['min_price'] ?? null, fn ($q, $minPrice) => $q->where('daily_rate', '>=', $minPrice))
            ->when($filters['max_price'] ?? null, fn ($q, $maxPrice) => $q->where('daily_rate', '<=', $maxPrice))
            ->when($filters['transmission'] ?? null, fn ($q, $transmission) => $q->where('transmission', $transmission))
            ->when($filters['fuel_type'] ?? null, fn ($q, $fuelType) => $q->where('fuel_type', $fuelType))
            ->when($filters['min_seats'] ?? null, fn ($q, $minSeats) => $q->where('seats', '>=', $minSeats))
            ->when($filters['max_year'] ?? null, fn ($q, $maxYear) => $q->where('year', '<=', $maxYear))
            ->when($filters['min_year'] ?? null, fn ($q, $minYear) => $q->where('year', '>=', $minYear))
            ->when($filters['location'] ?? null, fn ($q, $location) => $q->where('location', 'like', "%{$location}%"));
    }

    /**
     * Scope for popular vehicles based on bookings and reviews
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount(['bookings', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->having('bookings_count', '>', 0)
            ->orderByRaw('(bookings_count * 0.7 + COALESCE(reviews_avg_rating, 0) * 0.3) DESC')
            ->limit($limit);
    }

    /**
     * Scope for vehicles with maintenance issues or violations
     */
    public function scopeRequiringAttention($query)
    {
        return $query->where(function ($mainQuery): void {
            $mainQuery->where('status', 'maintenance')
                ->orWhere('insurance_expiry', '<=', now()->addDays(30))
                ->orWhere('has_pending_violations', true)
                ->orWhereJsonLength('traffic_violations', '>', 0);
        });
    }

    /**
     * Scope for revenue analysis by owner
     */
    public function scopeRevenueAnalysis($query, $startDate = null, $endDate = null)
    {
        $startDate ??= now()->subYear();
        $endDate ??= now();

        return $query->withSum(['bookings' => function ($bookingQuery) use ($startDate, $endDate): void {
            $bookingQuery->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate]);
        }], 'total_amount')
            ->withCount(['bookings' => function ($bookingQuery) use ($startDate, $endDate): void {
                $bookingQuery->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->orderBy('bookings_sum_total_amount', 'desc');
    }

    /**
     * Scope for nearby vehicles using location matching
     */
    public function scopeNearby($query, $location, $radius = 50)
    {
        // Simple text-based proximity (in real app, use spatial queries)
        return $query->where('location', 'like', "%{$location}%")
            ->orWhere('pickup_location', 'like', "%{$location}%");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vehicle')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Vehicle was added to the system',
                'updated' => 'Vehicle details were updated',
                'deleted' => 'Vehicle was removed from the system',
                default => "Vehicle was {$eventName}"
            });
    }
}
