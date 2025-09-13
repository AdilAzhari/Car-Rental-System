<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Reservations/Create');
    }
}
