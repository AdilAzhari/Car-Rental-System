<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create users
    $this->admin = User::factory()->admin()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
    ]);

    $this->owner = User::factory()->owner()->create([
        'name' => 'Test Owner',
        'email' => 'owner@test.com',
    ]);

    $this->renter = User::factory()->renter()->create([
        'name' => 'Test Renter',
        'email' => 'renter@test.com',
    ]);

    // Create vehicle and booking
    $this->vehicle = Vehicle::factory()->create([
        'owner_id' => $this->owner->id,
        'status' => 'published',
        'daily_rate' => 100.00,
    ]);

    $this->booking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'total_amount' => 300.00,
        'status' => 'confirmed',
    ]);

    // Create sample payment
    $this->payment = Payment::factory()->create([
        'booking_id' => $this->booking->id,
        'amount' => 300.00,
        'payment_method' => 'credit_card',
        'payment_status' => 'confirmed',
        'transaction_id' => 'TXN123456',
    ]);
});

it('can list payments as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->assertSee('Payments')
        ->assertSee($this->payment->transaction_id)
        ->assertSee('RM 300.00')
        ->assertSee($this->renter->name)
        ->assertNoJavascriptErrors();
});

it('can view payment details as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->click('View')
        ->assertSee($this->payment->transaction_id)
        ->assertSee('RM 300.00')
        ->assertSee($this->renter->name)
        ->assertSee($this->payment->payment_status->getLabel())
        ->assertNoJavascriptErrors();
});

it('can create new payment as admin', function (): void {
    // Create another booking for testing
    $newBooking = Booking::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'renter_id' => $this->renter->id,
        'total_amount' => 150.00,
        'status' => 'confirmed',
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->click('New payment')
        ->select('data.booking_id', $newBooking->id)
        ->fill('data.amount', '150.00')
        ->select('data.payment_method', 'bank_transfer')
        ->select('data.payment_status', 'pending')
        ->fill('data.transaction_id', 'TXN789012')
        ->press('Create')
        ->assertSee('Payment created successfully')
        ->assertNoJavascriptErrors();

    expect(Payment::where('transaction_id', 'TXN789012')->count())->toBe(1);
});

it('can edit payment as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/payments/{$this->payment->id}/edit")
        ->select('data.payment_status', 'confirmed')
        ->fill('data.notes', 'Payment verified and processed')
        ->press('Save changes')
        ->assertSee('Payment updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->payment->refresh()->notes)->toBe('Payment verified and processed');
});

it('can update payment status as admin', function (): void {
    $pendingPayment = Payment::factory()->create([
        'booking_id' => $this->booking->id,
        'payment_status' => 'pending',
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/payments/{$pendingPayment->id}/edit")
        ->select('data.payment_status', 'confirmed')
        ->press('Save changes')
        ->assertSee('Payment updated successfully')
        ->assertNoJavascriptErrors();

    expect($pendingPayment->refresh()->payment_status->value)->toBe('confirmed');
});

it('can process refund as admin', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit("/admin/payments/{$this->payment->id}/edit")
        ->select('data.payment_status', 'refunded')
        ->fill('data.refund_amount', '300.00')
        ->fill('data.refund_reason', 'Booking cancelled by customer')
        ->press('Save changes')
        ->assertSee('Payment updated successfully')
        ->assertNoJavascriptErrors();

    expect($this->payment->refresh()->payment_status->value)->toBe('refunded');
});

it('can filter payments by status', function (): void {
    // Create payments with different statuses
    Payment::factory()->create([
        'booking_id' => $this->booking->id,
        'payment_status' => 'pending',
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->click('Filters')
        ->select('tableFilters.payment_status.value', 'pending')
        ->press('Apply')
        ->assertSee('pending')
        ->assertNoJavascriptErrors();
});

it('can filter payments by method', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->click('Filters')
        ->select('tableFilters.payment_method.value', 'credit_card')
        ->press('Apply')
        ->assertSee('credit_card')
        ->assertNoJavascriptErrors();
});

it('can search payments by transaction ID', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->fill('tableSearch', $this->payment->transaction_id)
        ->assertSee($this->payment->transaction_id)
        ->assertNoJavascriptErrors();
});

it('owner can only see payments for their vehicles', function (): void {
    // Create payment for another owner's vehicle
    $otherOwner = User::factory()->owner()->create();
    $otherVehicle = Vehicle::factory()->create(['owner_id' => $otherOwner->id]);
    $otherBooking = Booking::factory()->create([
        'vehicle_id' => $otherVehicle->id,
        'renter_id' => $this->renter->id,
    ]);
    $otherPayment = Payment::factory()->create(['booking_id' => $otherBooking->id]);

    $page = visit('/admin/login')
        ->fill('email', $this->owner->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->assertSee($this->payment->transaction_id) // Should see own vehicle payment
        ->assertDontSee($otherPayment->transaction_id) // Should not see other's payment
        ->assertNoJavascriptErrors();
});

it('can bulk update payment status as admin', function (): void {
    $payment2 = Payment::factory()->create([
        'booking_id' => $this->booking->id,
        'payment_status' => 'pending',
    ]);

    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->check('recordCheckbox.0')
        ->check('recordCheckbox.1')
        ->click('Bulk actions')
        ->click('Update status to confirmed')
        ->press('Update')
        ->assertSee('Payments updated successfully')
        ->assertNoJavascriptErrors();
});

it('shows payment statistics correctly', function (): void {
    $page = visit('/admin/login')
        ->fill('email', $this->admin->email)
        ->fill('password', 'password')
        ->press('Sign in')
        ->visit('/admin/payments')
        ->assertSee('Total Revenue')
        ->assertSee('Pending Payments')
        ->assertNoJavascriptErrors();
});
