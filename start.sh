#!/usr/bin/env bash

# Run database migrations
php artisan migrate --force

# Start the web server
vendor/bin/heroku-php-apache2 public/