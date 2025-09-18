#!/usr/bin/env bash

set -o errexit  # exit on error

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies and build assets
npm ci
npm run build

# Clear and cache Laravel configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink if it doesn't exist
php artisan storage:link

echo "Build completed successfully!"