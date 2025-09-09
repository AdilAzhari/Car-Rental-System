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
        'plate_number',
        'vin',
        'fuel_type',
        'transmission',
        'daily_rate',
        'oil_type',
        'last_oil_change',
        'policy',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'year' => 'integer',
            'last_oil_change' => 'date',
            'fuel_type' => VehicleFuelType::class,
            'transmission' => VehicleTransmission::class,
            'status' => VehicleStatus::class,
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
