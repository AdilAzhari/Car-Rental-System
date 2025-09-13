<?php

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Payment Model', function (): void {
    beforeEach(function (): void {
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->booking = Booking::factory()->create([
            'renter_id' => $this->renter->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $this->payment = Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'amount' => 400.00,
            'method' => 'stripe',
            'status' => PaymentStatus::CONFIRMED,
        ]);
    });

    it('belongs to a booking', function (): void {
        expect($this->payment->booking)->toBeInstanceOf(Booking::class);
        expect($this->payment->booking->id)->toBe($this->booking->id);
    });

    it('uses correct table name', function (): void {
        expect($this->payment->getTable())->toBe('car_rental_payments');
    });

    it('has correct fillable attributes', function (): void {
        $expectedFillable = [
            'booking_id', 'amount', 'currency', 'type', 'method', 'status',
            'transaction_reference', 'external_id', 'processed_at', 'gateway_fee',
            'gateway_reference', 'payer_name', 'payer_email', 'payer_phone',
            'description', 'stripe_customer_id', 'stripe_payment_method_id',
            'card_brand', 'card_last_four', 'card_country', 'tng_phone_number',
            'tng_reference_id', 'tng_verification_status', 'tng_verified_at',
            'cash_received_by', 'store_location', 'cash_amount_received',
            'change_given', 'cash_verification', 'cash_notes', 'failure_reason',
            'failure_details', 'refund_amount', 'refunded_at', 'refund_reference',
            'refund_reason',
        ];

        expect($this->payment->getFillable())->toEqual($expectedFillable);
    });

    it('casts attributes correctly', function (): void {
        expect($this->payment->amount)->toBeFloat();
        expect($this->payment->gateway_fee)->toBeFloat();
        expect($this->payment->refund_amount)->toBeFloat();
        expect($this->payment->status)->toBeInstanceOf(PaymentStatus::class);
    });

    it('casts dates correctly', function (): void {
        $payment = Payment::factory()->create([
            'processed_at' => Carbon::now(),
            'tng_verified_at' => Carbon::now(),
            'refunded_at' => Carbon::now(),
        ]);

        expect($payment->processed_at)->toBeInstanceOf(Carbon::class);
        expect($payment->tng_verified_at)->toBeInstanceOf(Carbon::class);
        expect($payment->refunded_at)->toBeInstanceOf(Carbon::class);
    });

    it('can have different payment methods', function (): void {
        $stripePayment = Payment::factory()->create(['method' => 'stripe']);
        $tngPayment = Payment::factory()->create(['method' => 'tng']);
        $cashPayment = Payment::factory()->create(['method' => 'cash']);

        expect($stripePayment->method)->toBe('stripe');
        expect($tngPayment->method)->toBe('tng');
        expect($cashPayment->method)->toBe('cash');
    });

    it('can have different payment statuses', function (): void {
        $pending = Payment::factory()->create(['status' => PaymentStatus::PENDING]);
        $confirmed = Payment::factory()->create(['status' => PaymentStatus::CONFIRMED]);
        $failed = Payment::factory()->create(['status' => PaymentStatus::FAILED]);
        $refunded = Payment::factory()->create(['status' => PaymentStatus::REFUNDED]);

        expect($pending->status)->toBe(PaymentStatus::PENDING);
        expect($confirmed->status)->toBe(PaymentStatus::CONFIRMED);
        expect($failed->status)->toBe(PaymentStatus::FAILED);
        expect($refunded->status)->toBe(PaymentStatus::REFUNDED);
    });

    it('can store stripe specific fields', function (): void {
        $stripePayment = Payment::factory()->create([
            'method' => 'stripe',
            'stripe_customer_id' => 'cus_test123',
            'stripe_payment_method_id' => 'pm_test123',
            'card_brand' => 'visa',
            'card_last_four' => '4242',
            'card_country' => 'US',
        ]);

        expect($stripePayment->stripe_customer_id)->toBe('cus_test123');
        expect($stripePayment->stripe_payment_method_id)->toBe('pm_test123');
        expect($stripePayment->card_brand)->toBe('visa');
        expect($stripePayment->card_last_four)->toBe('4242');
        expect($stripePayment->card_country)->toBe('US');
    });

    it('can store tng specific fields', function (): void {
        $tngPayment = Payment::factory()->create([
            'method' => 'tng',
            'tng_phone_number' => '+60123456789',
            'tng_reference_id' => 'TNG123456',
            'tng_verification_status' => 'verified',
        ]);

        expect($tngPayment->tng_phone_number)->toBe('+60123456789');
        expect($tngPayment->tng_reference_id)->toBe('TNG123456');
        expect($tngPayment->tng_verification_status)->toBe('verified');
    });

    it('can store cash specific fields', function (): void {
        $cashPayment = Payment::factory()->create([
            'method' => 'cash',
            'cash_received_by' => 'Store Manager',
            'store_location' => 'Main Branch',
            'cash_amount_received' => 450.00,
            'change_given' => 50.00,
            'cash_verification' => 'counted',
        ]);

        expect($cashPayment->cash_received_by)->toBe('Store Manager');
        expect($cashPayment->store_location)->toBe('Main Branch');
        expect($cashPayment->cash_amount_received)->toBe(450.00);
        expect($cashPayment->change_given)->toBe(50.00);
        expect($cashPayment->cash_verification)->toBe('counted');
    });

    it('can handle refunds', function (): void {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::REFUNDED,
            'refund_amount' => 200.00,
            'refunded_at' => Carbon::now(),
            'refund_reference' => 'REF123456',
            'refund_reason' => 'Customer cancellation',
        ]);

        expect($payment->refund_amount)->toBe(200.00);
        expect($payment->refunded_at)->toBeInstanceOf(Carbon::class);
        expect($payment->refund_reference)->toBe('REF123456');
        expect($payment->refund_reason)->toBe('Customer cancellation');
    });

    it('can handle payment failures', function (): void {
        $failedPayment = Payment::factory()->create([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => 'insufficient_funds',
            'failure_details' => 'Card was declined due to insufficient funds',
        ]);

        expect($failedPayment->failure_reason)->toBe('insufficient_funds');
        expect($failedPayment->failure_details)->toBe('Card was declined due to insufficient funds');
    });
});
