<?php
// Create a debug route to test Filament authentication
use Illuminate\Support\Facades\Route;

// Add this to web.php temporarily to debug auth issues
Route::get('/debug-admin-auth', function () {
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => 'No authenticated user',
            'session_id' => session()->getId(),
            'auth_guard' => config('auth.defaults.guard'),
        ]);
    }

    return response()->json([
        'authenticated' => true,
        'user_id' => $user->id,
        'user_email' => $user->email,
        'user_role' => $user->role->value,
        'can_access_panel' => $user->canAccessPanel(app(\Filament\Panel::class)),
        'session_id' => session()->getId(),
        'auth_guard' => config('auth.defaults.guard'),
        'user_table' => $user->getTable(),
    ]);
});

echo "Debug route created. This file should be added to routes/web.php\n";
echo "Then test at: /debug-admin-auth\n";