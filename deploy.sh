#!/bin/bash

# SENTIENTS A.I Car Rental System - Production Deployment Script
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment of SENTIENTS A.I Car Rental System..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    print_error "Please don't run this script as root"
    exit 1
fi

# Backup existing application if it exists
if [ -d "storage" ]; then
    print_status "Creating backup..."
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S) 2>/dev/null || true
    print_success "Backup created"
fi

# Put application in maintenance mode
print_status "Enabling maintenance mode..."
php artisan down --message="Upgrading application. Please check back in a few minutes." || true

# Pull latest changes (if using git)
if [ -d ".git" ]; then
    print_status "Pulling latest changes..."
    git pull origin main
    print_success "Code updated"
fi

# Install/update dependencies
print_status "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction
print_success "PHP dependencies installed"

print_status "Installing Node.js dependencies..."
npm ci --production=false
print_success "Node.js dependencies installed"

# Build assets
print_status "Building frontend assets..."
npm run build
print_success "Frontend assets built"

# Database operations
print_status "Running database migrations..."
php artisan migrate --force
print_success "Database migrations completed"

# Clear and cache everything
print_status "Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

print_status "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Run our custom optimization
print_status "Running performance optimizations..."
php artisan app:optimize-performance
print_success "Performance optimizations applied"

# Set correct permissions
print_status "Setting file permissions..."
sudo chown -R www-data:www-data storage/ bootstrap/cache/ 2>/dev/null || {
    print_warning "Could not set www-data permissions. Please run manually:"
    print_warning "sudo chown -R www-data:www-data storage/ bootstrap/cache/"
}

chmod -R 775 storage/ bootstrap/cache/
print_success "File permissions set"

# Create storage link if it doesn't exist
if [ ! -L public/storage ]; then
    print_status "Creating storage symlink..."
    php artisan storage:link
    print_success "Storage symlink created"
fi

# Restart services
print_status "Restarting services..."

# Restart PHP-FPM
sudo systemctl reload php8.4-fpm 2>/dev/null || {
    sudo systemctl reload php-fpm 2>/dev/null || {
        print_warning "Could not restart PHP-FPM. Please restart manually."
    }
}

# Restart Nginx
sudo systemctl reload nginx 2>/dev/null || {
    print_warning "Could not restart Nginx. Please restart manually."
}

# Exit maintenance mode
print_status "Disabling maintenance mode..."
php artisan up
print_success "Application is now live"

# Final checks
print_status "Running final health checks..."

# Check if application is responding
if curl -s -o /dev/null -w "%{http_code}" "http://localhost" | grep -q "200\|302"; then
    print_success "Application is responding correctly"
else
    print_warning "Application might not be responding correctly. Please check manually."
fi

# Check queue workers (if applicable)
if pgrep -f "php.*artisan.*queue:work" > /dev/null; then
    print_success "Queue workers are running"
else
    print_warning "No queue workers detected. Start them if needed with: php artisan queue:work"
fi

print_success "🎉 Deployment completed successfully!"
echo ""
print_status "📊 Deployment Summary:"
echo "   ✅ Code updated"
echo "   ✅ Dependencies installed"
echo "   ✅ Assets built"
echo "   ✅ Database migrated"
echo "   ✅ Application optimized"
echo "   ✅ Permissions set"
echo "   ✅ Services restarted"
echo ""
print_status "🔗 Next steps:"
echo "   • Monitor logs: tail -f storage/logs/laravel.log"
echo "   • Check performance: php artisan app:optimize-performance"
echo "   • Monitor database: php artisan db:monitor"
echo ""
print_status "🎯 Your SENTIENTS A.I Car Rental System is ready for clients!"