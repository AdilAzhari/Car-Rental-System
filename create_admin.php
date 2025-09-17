<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;

// Create admin user
$admin = User::firstOrCreate(
    ['email' => 'admin@carrentalsystem.com'],
    [
        'name' => 'Admin User',
        'email' => 'admin@carrentalsystem.com',
        'password' => Hash::make('admin123'),
        'role' => UserRole::ADMIN,
        'status' => UserStatus::ACTIVE,
        'is_verified' => true,
        'email_verified_at' => now(),
    ]
);

echo "Admin user created successfully!\n";
echo "Email: admin@carrentalsystem.com\n";
echo "Password: admin123\n";
echo "User ID: " . $admin->id . "\n";
echo "Role: " . $admin->role->value . "\n";