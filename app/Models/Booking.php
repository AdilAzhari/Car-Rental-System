<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
