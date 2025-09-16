<?php
// Test authentication for admin user
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Authentication Test ===\n";

// Find admin user
$admin = User::where('email', 'admin@carrental.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✅ Admin user found: {$admin->email}\n";

// Test password
$testPassword = 'admin123';
if (Hash::check($testPassword, $admin->password)) {
    echo "✅ Password verification successful\n";
} else {
    echo "❌ Password verification failed\n";
}

// Try to authenticate the user
try {
    Auth::login($admin);
    if (Auth::check()) {
        echo "✅ Authentication successful\n";
        echo "Authenticated user: " . Auth::user()->email . "\n";
        echo "Authenticated user role: " . Auth::user()->role->value . "\n";

        // Test canAccessPanel with proper parameter
        $canAccess = Auth::user()->role === \App\Enums\UserRole::ADMIN;
        echo "Can access admin panel: " . ($canAccess ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Authentication failed\n";
    }
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
}