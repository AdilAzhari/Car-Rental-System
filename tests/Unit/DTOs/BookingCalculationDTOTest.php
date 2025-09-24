<?php

use App\DTOs\BookingCalculationDTO;

describe('BookingCalculationDTO', function (): void {
    it('calculates booking totals correctly', function (): void {
        $bookingCalculationDTO = BookingCalculationDTO::calculate(
            dailyRate: 100.0,
            totalDays: 3
        );

        expect($bookingCalculationDTO->dailyRate)->toBe(100.0)
            ->and($bookingCalculationDTO->totalDays)->toBe(3)
            ->and($bookingCalculationDTO->subtotal)->toBe(300.0)
            ->and($bookingCalculationDTO->insuranceFee)->toBe(30.0)
            ->and($bookingCalculationDTO->taxAmount)->toBeCloseTo(26.4, 2)
            ->and($bookingCalculationDTO->totalAmount)->toBeCloseTo(356.4, 2);
        // 10% of subtotal
        // 8% of (subtotal + insurance)
        // subtotal + insurance + tax
    });

    it('handles zero days correctly', function (): void {
        $bookingCalculationDTO = BookingCalculationDTO::calculate(
            dailyRate: 100.0,
            totalDays: 0
        );

        expect($bookingCalculationDTO->subtotal)->toBe(0.0)
            ->and($bookingCalculationDTO->totalAmount)->toBe(0.0);
    });

    it('converts to array with formatted amounts', function (): void {
        $bookingCalculationDTO = BookingCalculationDTO::calculate(
            dailyRate: 99.99,
            totalDays: 2
        );

        $array = $bookingCalculationDTO->toArray();

        expect($array)->toHaveKeys([
            'daily_rate', 'total_days', 'subtotal',
            'insurance_fee', 'tax_amount', 'total_amount', 'currency',
        ])
            ->and($array['daily_rate'])->toBeString()->
            expect($array['total_amount'])->toBeString()->
            expect($array['currency'])->toBe('MYR');
    });

    it('formats total with currency', function (): void {
        $calculation = new BookingCalculationDTO(
            dailyRate: 100.0,
            totalDays: 2,
            subtotal: 200.0,
            insuranceFee: 20.0,
            taxAmount: 17.6,
            totalAmount: 237.6,
            currency: 'MYR'
        );

        expect($calculation->getFormattedTotal())->toBe('MYR 237.60');
    });
});
