<?php

use App\Enums\PaymentMethod;
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
            'payment_method' => PaymentMethod::CREDIT->value,
            'payment_status' => PaymentStatus::CONFIRMED->value,
        ]);
    });

    it('belongs to a booking', function (): void {
        expect($this->payment->booking)->toBeInstanceOf(Booking::class)
            ->and($this->payment->booking->id)->toBe($this->booking->id);
    });

    it('uses correct table name', function (): void {
        expect($this->payment->getTable())->toBe('car_rental_payments');
    });

    it('has correct fillable attributes', function (): void {
        $expectedFillable = [
            'booking_id',
            'amount',
            'payment_method',
            'payment_status',
            'transaction_id',
            'gateway_response',
            'processed_at',
            'refunded_at',
            'refund_amount',
        ];

        expect($this->payment->getFillable())->toEqual($expectedFillable);
    });

    it('casts attributes correctly', function (): void {
        expect($this->payment->amount)->toBeString()
            ->and($this->payment->refund_amount)->toBeString();
    });

    it('casts dates correctly', function (): void {
        $payment = Payment::factory()->create([
            'processed_at' => Carbon::now(),
            'refunded_at' => Carbon::now(),
        ]);

        expect($payment->processed_at)->toBeInstanceOf(Carbon::class)
            ->and($payment->refunded_at)->toBeInstanceOf(Carbon::class);
    });

    it('can have different payment methods', function (): void {
        $visaPayment = Payment::factory()->create(['payment_method' => PaymentMethod::VISA->value]);
        $creditPayment = Payment::factory()->create(['payment_method' => PaymentMethod::CREDIT->value]);
        $cashPayment = Payment::factory()->create(['payment_method' => PaymentMethod::CASH->value]);

        expect($visaPayment->payment_method)->toBe(PaymentMethod::VISA->value)
            ->and($creditPayment->payment_method)->toBe(PaymentMethod::CREDIT->value)
            ->and($cashPayment->payment_method)->toBe(PaymentMethod::CASH->value);
    });

    it('can have different payment statuses', function (): void {
        $pending = Payment::factory()->create(['payment_status' => PaymentStatus::PENDING->value]);
        $confirmed = Payment::factory()->create(['payment_status' => PaymentStatus::CONFIRMED->value]);
        $failed = Payment::factory()->create(['payment_status' => PaymentStatus::FAILED->value]);
        $cancelled = Payment::factory()->create(['payment_status' => PaymentStatus::CANCELLED->value]);

        expect($pending->payment_status)->toBe(PaymentStatus::PENDING->value)
            ->and($confirmed->payment_status)->toBe(PaymentStatus::CONFIRMED->value)
            ->and($failed->payment_status)->toBe(PaymentStatus::FAILED->value)
            ->and($cancelled->payment_status)->toBe(PaymentStatus::CANCELLED->value);
    });

    it('can store gateway response data', function (): void {
        $gatewayResponse = [
            'gateway' => 'stripe',
            'response_code' => '200',
            'message' => 'Payment processed successfully',
            'reference_id' => 'REF-123456',
            'customer_id' => 'cus_test123',
            'payment_method_id' => 'pm_test123',
        ];

        $payment = Payment::factory()->create([
            'gateway_response' => $gatewayResponse,
        ]);

        expect($payment->gateway_response)->toBe($gatewayResponse)
            ->and($payment->gateway_response['gateway'])->toBe('stripe')
            ->and($payment->gateway_response['customer_id'])->toBe('cus_test123');
    })->skip();

    it('can store digital payment data in gateway response', function (): void {
        $gatewayResponse = [
            'gateway' => 'paypal',
            'response_code' => '200',
            'message' => 'Payment processed successfully',
            'reference_id' => 'REF-456789',
            'phone_number' => '+60123456789',
            'verification_status' => 'verified',
        ];

        $payment = Payment::factory()->create([
            'payment_method' => PaymentMethod::PAYPAL->value,
            'gateway_response' => $gatewayResponse,
        ]);

        expect($payment->gateway_response['gateway'])->toBe('paypal')
            ->and($payment->gateway_response['phone_number'])->toBe('+60123456789')
            ->and($payment->gateway_response['verification_status'])->toBe('verified');
    });

    it('can handle cash payments with gateway response data', function (): void {
        $gatewayResponse = [
            'received_by' => 'Store Manager',
            'location' => 'Main Branch',
            'amount_received' => 450.00,
            'change_given' => 50.00,
            'verification' => 'counted',
            'notes' => 'Payment received in full',
        ];

        $cashPayment = Payment::factory()->create([
            'payment_method' => PaymentMethod::CASH->value,
            'gateway_response' => $gatewayResponse,
        ]);

        expect($cashPayment->gateway_response['received_by'])->toBe('Store Manager')
            ->and($cashPayment->gateway_response['location'])->toBe('Main Branch')
            ->and($cashPayment->gateway_response['amount_received'])->toBe(450)
            ->and($cashPayment->gateway_response['change_given'])->toBe(50)
            ->and($cashPayment->gateway_response['verification'])->toBe('counted');
    });

    it('can handle refunds', function (): void {
        $refundDate = Carbon::now();
        $payment = Payment::factory()->create([
            'payment_status' => PaymentStatus::REFUNDED->value,
            'refund_amount' => 200.00,
            'refunded_at' => $refundDate,
        ]);

        expect($payment->refund_amount)->toBe('200.00')
            ->and($payment->refunded_at)->toBeInstanceOf(Carbon::class)
            ->and($payment->payment_status)->toBe(PaymentStatus::REFUNDED->value);
    });

    it('can handle payment failures', function (): void {
        $gatewayResponse = [
            'gateway' => 'stripe',
            'response_code' => '402',
            'message' => 'Card was declined due to insufficient funds',
            'reference_id' => 'REF-ERR123',
            'failure_reason' => 'insufficient_funds',
        ];

        $failedPayment = Payment::factory()->create([
            'payment_status' => PaymentStatus::FAILED->value,
            'gateway_response' => $gatewayResponse,
        ]);

        expect($failedPayment->payment_status)->toBe(PaymentStatus::FAILED->value)
            ->and($failedPayment->gateway_response['failure_reason'])->toBe('insufficient_funds')
            ->and($failedPayment->gateway_response['message'])->toBe('Card was declined due to insufficient funds');
    })->skip();
});
