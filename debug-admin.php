<?php
// Debug admin user access for Heroku
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Admin User Debug ===\n";

// Find admin user
$admin = User::where('email', 'admin@carrental.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✅ Admin user found:\n";
echo "- ID: " . $admin->id . "\n";
echo "- Name: " . $admin->name . "\n";
echo "- Email: " . $admin->email . "\n";
echo "- Role: " . $admin->role->value . "\n";
echo "- Status: " . $admin->status->value . "\n";
echo "- Is Verified: " . ($admin->is_verified ? 'Yes' : 'No') . "\n";
echo "- Created: " . $admin->created_at . "\n";

// Test canAccessPanel method
echo "\n=== Panel Access Test ===\n";

// Create a mock panel (Filament uses this interface)
$mockPanel = new class {
    public function getId(): string {
        return 'admin';
    }
};

try {
    $canAccess = $admin->canAccessPanel($mockPanel);
    echo "✅ canAccessPanel() result: " . ($canAccess ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "❌ Error calling canAccessPanel(): " . $e->getMessage() . "\n";
}

// Check role enum comparison
echo "\n=== Role Comparison Debug ===\n";
echo "- User role object: " . get_class($admin->role) . "\n";
echo "- User role value: " . $admin->role->value . "\n";
echo "- Admin enum: " . UserRole::ADMIN->value . "\n";
echo "- Direct comparison (===): " . ($admin->role === UserRole::ADMIN ? 'true' : 'false') . "\n";
echo "- Value comparison: " . ($admin->role->value === UserRole::ADMIN->value ? 'true' : 'false') . "\n";

echo "\n=== All User Roles ===\n";
$allUsers = User::all();
foreach ($allUsers as $user) {
    echo "- {$user->email}: {$user->role->value}\n";
}