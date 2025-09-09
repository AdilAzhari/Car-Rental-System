<?php

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Payment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

describe('Payment Management', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $this->renter = User::factory()->renter()->create();
        $this->vehicle = Vehicle::factory()->create(['owner_id' => $this->owner->id]);
        $this->booking = Booking::factory()->create([
            'renter_id' => $this->renter->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => BookingStatus::CONFIRMED
        ]);
    });

    describe('Payment Listing', function () {
        it('allows admin to view all payments', function () {
            Payment::factory(3)->create(['booking_id' => $this->booking->id]);
            
            $this->actingAs($this->admin)
                ->get('/admin/payments')
                ->assertSuccessful()
                ->assertSee('Payments');
        });

        it('allows owner to view payments for their vehicle bookings', function () {
            Payment::factory(2)->create(['booking_id' => $this->booking->id]);
            
            $this->actingAs($this->owner)
                ->get('/admin/payments')
                ->assertSuccessful();
        });

        it('restricts renter access to payment management', function () {
            $this->actingAs($this->renter)
                ->get('/admin/payments')
                ->assertForbidden();
        });

        it('filters payments by status', function () {
            Payment::factory()->create([
                'booking_id' => $this->booking->id,
                'status' => PaymentStatus::PENDING
            ]);
            
            $this->actingAs($this->admin)
                ->get('/admin/payments?status=pending')
                ->assertSuccessful();
        });

        it('filters payments by method', function () {
            Payment::factory()->create([
                'booking_id' => $this->booking->id,
                'method' => 'stripe'
            ]);
            
            $this->actingAs($this->admin)
                ->get('/admin/payments?method=stripe')
                ->assertSuccessful();
        });
    });

    describe('Payment Processing', function () {
        it('allows creating stripe payment', function () {
            $paymentData = [
                'booking_id' => $this->booking->id,
                'amount' => 400.00,
                'method' => 'stripe',
                'status' => PaymentStatus::PENDING->value,
                'stripe_customer_id' => 'cus_test123',
                'card_brand' => 'visa',
                'card_last_four' => '4242'
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/payments', $paymentData);
            
            $payment = Payment::where('booking_id', $this->booking->id)->first();
            expect($payment->method)->toBe('stripe');
            expect($payment->card_brand)->toBe('visa');
        });

        it('allows creating cash payment', function () {
            $paymentData = [
                'booking_id' => $this->booking->id,
                'amount' => 400.00,
                'method' => 'cash',
                'status' => PaymentStatus::CONFIRMED->value,
                'cash_received_by' => 'Store Manager',
                'store_location' => 'Main Branch'
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/payments', $paymentData);
            
            $payment = Payment::where('booking_id', $this->booking->id)->first();
            expect($payment->method)->toBe('cash');
            expect($payment->cash_received_by)->toBe('Store Manager');
        });

        it('validates payment amount against booking total', function () {
            $paymentData = [
                'booking_id' => $this->booking->id,
                'amount' => -100.00, // Negative amount
                'method' => 'stripe',
                'status' => PaymentStatus::PENDING->value
            ];
            
            $this->actingAs($this->admin)
                ->post('/admin/payments', $paymentData)
                ->assertSessionHasErrors(['amount']);
        });
    });

    describe('Payment Status Updates', function () {
        beforeEach(function () {
            $this->payment = Payment::factory()->create([
                'booking_id' => $this->booking->id,
                'status' => PaymentStatus::PENDING
            ]);
        });

        it('allows admin to confirm payment', function () {
            $this->actingAs($this->admin)
                ->patch("/admin/payments/{$this->payment->id}", [
                    'status' => PaymentStatus::CONFIRMED->value,
                    'processed_at' => Carbon::now()->toDateTimeString()
                ]);
            
            $this->payment->refresh();
            expect($this->payment->status)->toBe(PaymentStatus::CONFIRMED);
            expect($this->payment->processed_at)->not->toBeNull();
        });

        it('allows marking payment as failed with reason', function () {
            $this->actingAs($this->admin)
                ->patch("/admin/payments/{$this->payment->id}", [
                    'status' => PaymentStatus::FAILED->value,
                    'failure_reason' => 'insufficient_funds',
                    'failure_details' => 'Card declined'
                ]);
            
            $this->payment->refresh();
            expect($this->payment->status)->toBe(PaymentStatus::FAILED);
            expect($this->payment->failure_reason)->toBe('insufficient_funds');
        });
    });

    describe('Payment Refunds', function () {
        beforeEach(function () {
            $this->payment = Payment::factory()->create([
                'booking_id' => $this->booking->id,
                'status' => PaymentStatus::CONFIRMED,
                'amount' => 400.00
            ]);
        });

        it('allows processing partial refund', function () {
            $refundData = [
                'status' => PaymentStatus::REFUNDED->value,
                'refund_amount' => 200.00,
                'refund_reason' => 'Customer cancellation',
                'refunded_at' => Carbon::now()->toDateTimeString()
            ];
            
            $this->actingAs($this->admin)
                ->patch("/admin/payments/{$this->payment->id}", $refundData);
            
            $this->payment->refresh();
            expect($this->payment->status)->toBe(PaymentStatus::REFUNDED);
            expect($this->payment->refund_amount)->toBe(200.00);
        });

        it('prevents refund amount exceeding original amount', function () {
            $refundData = [
                'refund_amount' => 500.00, // More than original 400.00
                'refund_reason' => 'Customer cancellation'
            ];
            
            $this->actingAs($this->admin)
                ->patch("/admin/payments/{$this->payment->id}", $refundData)
                ->assertSessionHasErrors(['refund_amount']);
        });
    });

    describe('Payment Reports', function () {
        it('displays payment analytics', function () {
            Payment::factory(5)->create(['booking_id' => $this->booking->id]);
            
            $this->actingAs($this->admin)
                ->get('/admin/payments/reports')
                ->assertSuccessful();
        });

        it('exports payment data', function () {
            Payment::factory(3)->create(['booking_id' => $this->booking->id]);
            
            $this->actingAs($this->admin)
                ->get('/admin/payments/export')
                ->assertSuccessful();
        });
    });

    describe('Gateway Integration', function () {
        it('handles stripe webhook processing', function () {
            $webhookData = [
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test123',
                        'amount' => 40000, // Stripe amounts in cents
                        'status' => 'succeeded'
                    ]
                ]
            ];
            
            $this->post('/webhooks/stripe', $webhookData)
                ->assertSuccessful();
        });

        it('validates webhook signatures', function () {
            $webhookData = [
                'type' => 'payment_intent.succeeded',
                'data' => []
            ];
            
            // Without proper signature header
            $this->post('/webhooks/stripe', $webhookData)
                ->assertStatus(400);
        });
    });
});