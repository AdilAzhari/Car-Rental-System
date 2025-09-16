<?php
// Simple admin user creation script for Heroku
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create or update admin user
$admin = User::updateOrCreate(
    ['email' => 'admin@carrental.com'],
    [
        'name' => 'System Administrator',
        'password' => Hash::make('admin123'),
        'phone' => '+1-555-000-0001',
        'role' => UserRole::ADMIN,
        'status' => UserStatus::ACTIVE,
        'is_verified' => true,
        'date_of_birth' => '1980-01-15',
        'address' => '123 Admin Boulevard, Corporate City, CC 10001',
        'email_verified_at' => now(),
    ]
);

echo "Admin user created successfully!\n";
echo "Email: admin@carrental.com\n";
echo "Password: admin123\n";
echo "User ID: " . $admin->id . "\n";
echo "Role: " . $admin->role->value . "\n";