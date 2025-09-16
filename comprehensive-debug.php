<?php
// Comprehensive debug script to diagnose the 403 Forbidden issue
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== COMPREHENSIVE HEROKU DEBUGGING ===\n\n";

echo "1. ENVIRONMENT CHECK:\n";
echo "- APP_ENV: " . env('APP_ENV') . "\n";
echo "- APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "- APP_URL: " . env('APP_URL') . "\n";
echo "- SESSION_DRIVER: " . env('SESSION_DRIVER') . "\n";
echo "- CACHE_STORE: " . env('CACHE_STORE') . "\n";
echo "- PHP Version: " . PHP_VERSION . "\n\n";

echo "2. DATABASE CONNECTION CHECK:\n";
try {
    DB::connection()->getPdo();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n3. ADMIN USER CHECK:\n";
$admin = User::where('email', 'admin@carrental.com')->first();
if ($admin) {
    echo "✅ Admin user exists:\n";
    echo "   - ID: {$admin->id}\n";
    echo "   - Email: {$admin->email}\n";
    echo "   - Role: {$admin->role->value}\n";
    echo "   - Status: {$admin->status->value}\n";
    echo "   - Is Verified: " . ($admin->is_verified ? 'true' : 'false') . "\n";
    echo "   - Table: {$admin->getTable()}\n";
    echo "   - Role Type: " . get_class($admin->role) . "\n";
    echo "   - Role Enum Comparison: " . ($admin->role === UserRole::ADMIN ? 'MATCH' : 'NO MATCH') . "\n\n";

    // Check canAccessPanel method
    echo "4. FILAMENT PANEL ACCESS CHECK:\n";
    try {
        $panel = \Filament\Facades\Filament::getPanel('admin');
        echo "✅ Filament admin panel found\n";
        echo "   - Panel ID: {$panel->getId()}\n";
        echo "   - Panel Path: {$panel->getPath()}\n";

        $canAccess = $admin->canAccessPanel($panel);
        echo "   - canAccessPanel() result: " . ($canAccess ? 'TRUE' : 'FALSE') . "\n";

        if (!$canAccess) {
            echo "   - DEBUGGING canAccessPanel logic:\n";
            echo "     * User role value: '{$admin->role->value}'\n";
            echo "     * Admin enum value: '" . UserRole::ADMIN->value . "'\n";
            echo "     * String comparison: " . ($admin->role->value === UserRole::ADMIN->value ? 'MATCH' : 'NO MATCH') . "\n";
            echo "     * Object comparison: " . ($admin->role === UserRole::ADMIN ? 'MATCH' : 'NO MATCH') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Filament panel error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Admin user not found!\n";
}

echo "\n5. FILAMENT CONFIGURATION CHECK:\n";
try {
    $panels = \Filament\Facades\Filament::getPanels();
    echo "✅ Available panels: " . implode(', ', array_keys($panels)) . "\n";

    if (isset($panels['admin'])) {
        $adminPanel = $panels['admin'];
        echo "   - Admin Panel Class: " . get_class($adminPanel) . "\n";
        echo "   - Admin Panel URL: " . $adminPanel->getUrl() . "\n";
        echo "   - Auth Guard: " . ($adminPanel->getAuthGuard() ?? 'default') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Filament configuration error: " . $e->getMessage() . "\n";
}

echo "\n6. AUTHENTICATION CONFIGURATION:\n";
echo "- Default Guard: " . config('auth.defaults.guard') . "\n";
echo "- Web Guard Driver: " . config('auth.guards.web.driver') . "\n";
echo "- Web Guard Provider: " . config('auth.guards.web.provider') . "\n";
echo "- User Provider Model: " . config('auth.providers.users.model') . "\n";

echo "\n7. MIDDLEWARE AND ROUTES CHECK:\n";
try {
    $routes = app('router')->getRoutes();
    $adminRoutes = [];
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'admin')) {
            $adminRoutes[] = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'middleware' => $route->gatherMiddleware(),
            ];
        }
    }
    echo "Found " . count($adminRoutes) . " admin routes\n";
    if (count($adminRoutes) > 0) {
        echo "Sample admin route middleware: " . implode(', ', $adminRoutes[0]['middleware']) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Route check error: " . $e->getMessage() . "\n";
}

echo "\nDEBUGGING COMPLETE\n";