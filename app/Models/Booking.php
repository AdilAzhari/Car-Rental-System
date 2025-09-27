<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'car_rental_bookings';

    protected $fillable = [
        'renter_id',
        'vehicle_id',
        'start_date',
        'end_date',
        'days',
        'daily_rate',
        'subtotal',
        'insurance_fee',
        'tax_amount',
        'total_amount',
        'status',
        'pickup_location',
        'dropoff_location',
        'special_requests',
        'deposit_amount',
        'commission_amount',
        'payment_status',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'daily_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'insurance_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function getDaysAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // === ELOQUENT SCOPES FOR COMPLEX BOOKING QUERIES ===

    /**
     * Scope bookings by status with role-based access
     */
    public function scopeByStatus($query, $status, $userRole = null)
    {
        $query->where('status', $status);

        // Role-based filtering
        if ($userRole === 'renter') {
            $query->where('renter_id', auth()->id());
        } elseif ($userRole === 'owner') {
            $query->whereHas('vehicle', fn ($q) => $q->where('owner_id', auth()->id()));
        }

        return $query;
    }

    /**
     * Scope for active bookings (ongoing or confirmed)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'ongoing'])
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for upcoming bookings
     */
    public function scopeUpcoming($query, $days = 30)
    {
        return $query->where('status', 'confirmed')
            ->where('start_date', '>', now())
            ->where('start_date', '<=', now()->addDays($days))
            ->orderBy('start_date');
    }

    /**
     * Scope for overdue returns
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'ongoing')
            ->where('end_date', '<', now())
            ->with(['renter', 'vehicle']);
    }

    /**
     * Scope for revenue analysis with date ranges
     */
    public function scopeRevenueInPeriod($query, $startDate = null, $endDate = null, $ownerId = null)
    {
        $startDate ??= now()->startOfMonth();
        $endDate ??= now()->endOfMonth();

        $query->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($ownerId) {
            $query->whereHas('vehicle', fn ($q) => $q->where('owner_id', $ownerId));
        }

        return $query->with(['vehicle', 'payments'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for bookings requiring attention (payments pending, reviews missing, etc.)
     */
    public function scopeRequiringAttention($query)
    {
        return $query->where(function ($mainQuery): void {
            $mainQuery->where(function ($paymentQuery): void {
                // Bookings with pending payments
                $paymentQuery->where('payment_status', 'pending')
                    ->where('created_at', '<', now()->subHours(24));
            })->orWhere(function ($completedQuery): void {
                // Completed bookings without reviews (older than 7 days)
                $completedQuery->where('status', 'completed')
                    ->where('end_date', '<', now()->subDays(7))
                    ->doesntHave('review');
            })->orWhere(function ($overdueQuery): void {
                // Overdue ongoing bookings
                $overdueQuery->where('status', 'ongoing')
                    ->where('end_date', '<', now());
            });
        });
    }
}
