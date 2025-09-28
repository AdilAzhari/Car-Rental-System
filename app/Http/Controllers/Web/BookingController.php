<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(): Response
    {
        $lengthAwarePaginator = Booking::with(['vehicle', 'vehicle.images'])
            ->where('renter_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('MyBookingsPage', [
            'bookings' => $lengthAwarePaginator,
        ]);
    }

    public function show(Booking $booking): Response
    {
        // Ensure user can only see their own bookings
        if ($booking->renter_id !== auth()->id()) {
            abort(403);
        }

        $booking->load(['vehicle', 'vehicle.images', 'vehicle.owner', 'payments']);

        return Inertia::render('BookingDetailsPage', [
            'booking' => $booking,
        ]);
    }

    public function paymentCheckout(Booking $booking): Response
    {
        // Ensure user can only access payment for their own bookings
        if ($booking->renter_id !== auth()->id()) {
            abort(403);
        }

        // Check if booking is in a valid state for payment
        if (! in_array($booking->status, ['pending', 'pending_payment'])) {
            return redirect()->route('booking.show', $booking)
                ->with('error', 'This booking cannot be paid for.');
        }

        $booking->load(['vehicle', 'vehicle.images']);

        return Inertia::render('PaymentCheckout', [
            'booking' => $booking,
            'stripe_key' => config('services.stripe.key'),
        ]);
    }

    public function paymentReturn(Booking $booking): Response
    {
        // This endpoint handles payment return callbacks from payment gateways
        $booking->load(['vehicle', 'payments']);

        return Inertia::render('PaymentReturn', [
            'booking' => $booking,
            'message' => 'Payment processing completed. Please check your booking status.',
        ]);
    }
}
