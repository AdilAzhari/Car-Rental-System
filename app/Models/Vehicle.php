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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('vehicle')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Vehicle was added to the system',
                'updated' => 'Vehicle details were updated',
                'deleted' => 'Vehicle was removed from the system',
                default => "Vehicle was {$eventName}"
            });
    }
}
