@echo off
echo Car Rental System - Laragon Setup
echo ====================================

echo.
echo Step 1: Installing dependencies...
composer install --ignore-platform-req=ext-pgsql --no-dev --optimize-autoloader

echo.
echo Step 2: Setting up environment...
if not exist .env (
    copy .env.example .env
    echo Environment file created
)

echo.
echo Step 3: Generating application key...
php artisan key:generate

echo.
echo Step 4: Setting up database...
echo Make sure Laragon MySQL is running, then:
echo - Create database 'car_rental_system' in phpMyAdmin
echo - Update .env file with correct database settings
echo - Run: php artisan migrate --seed

echo.
echo Step 5: Building assets...
npm install
npm run build

echo.
echo Step 6: Setting permissions...
if exist storage (
    echo Setting storage permissions...
)

echo.
echo Setup complete!
echo.
echo Next steps:
echo 1. Start Laragon MySQL
echo 2. Create database 'car_rental_system'
echo 3. Run: php artisan migrate --seed
echo 4. Run: php artisan serve
echo 5. Visit: http://localhost:8000
echo.
pause