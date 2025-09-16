#!/bin/bash

echo "Running post-deployment commands..."

# Generate application key if not set
php artisan key:generate --no-interaction

# Run database migrations
echo "Running migrations..."
php artisan migrate --no-interaction --force

# Create storage symbolic link
echo "Creating storage link..."
php artisan storage:link --no-interaction

# Clear and cache configuration
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Seed the database with initial data
echo "Seeding database..."
php artisan db:seed --no-interaction --force

echo "Post-deployment completed successfully!"