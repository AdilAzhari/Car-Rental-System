<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CarController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Cars/Index');
    }

    public function show(int $id): Response
    {
        return Inertia::render('Cars/Show', [
            'id' => $id,
        ]);
    }
}
