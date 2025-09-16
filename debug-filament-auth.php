<?php
// Debug Filament authentication specifically
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Add this temporarily to routes/web.php to debug
Route::get('/debug-filament-auth', function () {
    $user = Auth::user();

    $response = [
        'request_url' => request()->fullUrl(),
        'auth_check' => Auth::check(),
        'user' => null,
        'panel_access' => false,
        'session_data' => [
            'session_id' => session()->getId(),
            'session_token' => session()->token(),
            'auth_guard' => config('auth.defaults.guard'),
        ],
        'middlewares' => [],
        'filament_panel_exists' => false,
    ];

    if ($user) {
        $response['user'] = [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'is_verified' => $user->is_verified,
            'table' => $user->getTable(),
            'auth_identifier' => $user->getAuthIdentifier(),
        ];

        // Test canAccessPanel method
        try {
            $panel = \Filament\Facades\Filament::getPanel('admin');
            $response['filament_panel_exists'] = true;
            $response['panel_access'] = $user->canAccessPanel($panel);
            $response['panel_id'] = $panel->getId();
            $response['panel_path'] = $panel->getPath();
        } catch (Exception $e) {
            $response['panel_error'] = $e->getMessage();
        }
    }

    return response()->json($response);
})->name('debug.filament.auth');

echo "Debug route created. Add this to routes/web.php and visit /debug-filament-auth\n";