<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Inertia\Response;

class BookingController extends Controller
{

    public function index(): Response
    {
        $bookings = Booking::with(['vehicle', 'vehicle.images'])
            ->where('renter_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('MyBookingsPage', [
            'bookings' => $bookings
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
            'booking' => $booking
        ]);
    }

    public function paymentReturn(Booking $booking): Response
    {
        // This endpoint handles payment return callbacks from payment gateways
        $booking->load(['vehicle', 'payments']);

        return Inertia::render('PaymentReturn', [
            'booking' => $booking,
            'message' => 'Payment processing completed. Please check your booking status.'
        ]);
    }
}
