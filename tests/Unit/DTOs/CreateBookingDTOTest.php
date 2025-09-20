<?php

use App\DTOs\CreateBookingDTO;
use Carbon\Carbon;

describe('CreateBookingDTO', function () {
    it('can be created from array', function () {
        $data = [
            'car_id' => 1,
            'renter_id' => 2,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-03',
            'duration_days' => 3,
            'payment_method' => 'visa',
            'payment_method_id' => 'pm_test123',
            'pickup_location' => 'Downtown',
            'dropoff_location' => 'Airport',
            'special_requests' => 'Need GPS',
        ];

        $dto = CreateBookingDTO::fromArray($data);

        expect($dto->carId)->toBe(1);
        expect($dto->renterId)->toBe(2);
        expect($dto->startDate)->toBeInstanceOf(Carbon::class);
        expect($dto->endDate)->toBeInstanceOf(Carbon::class);
        expect($dto->durationDays)->toBe(3);
        expect($dto->paymentMethod)->toBe('visa');
        expect($dto->paymentMethodId)->toBe('pm_test123');
        expect($dto->pickupLocation)->toBe('Downtown');
        expect($dto->dropoffLocation)->toBe('Airport');
        expect($dto->specialRequests)->toBe('Need GPS');
    });

    it('can convert back to array', function () {
        $dto = new CreateBookingDTO(
            carId: 1,
            renterId: 2,
            startDate: Carbon::parse('2025-01-01'),
            endDate: Carbon::parse('2025-01-03'),
            durationDays: 3,
            paymentMethod: 'visa',
            paymentMethodId: 'pm_test123',
            pickupLocation: 'Downtown',
            dropoffLocation: 'Airport',
            specialRequests: 'Need GPS'
        );

        $array = $dto->toArray();

        expect($array)->toHaveKeys([
            'car_id', 'renter_id', 'start_date', 'end_date',
            'duration_days', 'payment_method', 'payment_method_id',
            'pickup_location', 'dropoff_location', 'special_requests'
        ]);
        expect($array['car_id'])->toBe(1);
        expect($array['start_date'])->toBe('2025-01-01');
        expect($array['end_date'])->toBe('2025-01-03');
    });

    it('validates date range correctly', function () {
        $futureStart = Carbon::tomorrow();
        $futureEnd = Carbon::tomorrow()->addDays(2);

        $dto = new CreateBookingDTO(
            carId: 1,
            renterId: 2,
            startDate: $futureStart,
            endDate: $futureEnd,
            durationDays: 3,
            paymentMethod: 'visa',
            paymentMethodId: null,
            pickupLocation: null,
            dropoffLocation: null,
            specialRequests: null
        );

        expect($dto->isValidDateRange())->toBeTrue();
    });

    it('detects invalid date range', function () {
        $dto = new CreateBookingDTO(
            carId: 1,
            renterId: 2,
            startDate: Carbon::yesterday(),
            endDate: Carbon::today(),
            durationDays: 1,
            paymentMethod: 'visa',
            paymentMethodId: null,
            pickupLocation: null,
            dropoffLocation: null,
            specialRequests: null
        );

        expect($dto->isValidDateRange())->toBeFalse();
    });

    it('determines when payment method ID is required', function () {
        $visaDto = new CreateBookingDTO(
            carId: 1,
            renterId: 2,
            startDate: Carbon::tomorrow(),
            endDate: Carbon::tomorrow()->addDay(),
            durationDays: 2,
            paymentMethod: 'visa',
            paymentMethodId: null,
            pickupLocation: null,
            dropoffLocation: null,
            specialRequests: null
        );

        $cashDto = new CreateBookingDTO(
            carId: 1,
            renterId: 2,
            startDate: Carbon::tomorrow(),
            endDate: Carbon::tomorrow()->addDay(),
            durationDays: 2,
            paymentMethod: 'cash',
            paymentMethodId: null,
            pickupLocation: null,
            dropoffLocation: null,
            specialRequests: null
        );

        expect($visaDto->requiresPaymentMethodId())->toBeTrue();
        expect($cashDto->requiresPaymentMethodId())->toBeFalse();
    });
});