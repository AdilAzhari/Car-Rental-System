# Multi-stage Dockerfile for Laravel Car Rental System
# Optimized for production deployment with minimal image size

# PHP version
ARG PHP_VERSION=8.4
ARG NODE_VERSION=22

################################################################################
# Stage 1: PHP Dependencies
FROM php:${PHP_VERSION}-fpm-alpine AS php-deps

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (production only)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy application files
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

################################################################################
# Stage 2: Node.js Assets Build
FROM node:${NODE_VERSION}-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source files needed for build
COPY . .

# Build assets for production
RUN npm run build

################################################################################
# Stage 3: Production Image
FROM php:${PHP_VERSION}-fpm-alpine AS production

# Install runtime dependencies only
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    libwebp \
    libzip \
    icu \
    postgresql-libs \
    nginx \
    supervisor \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Configure PHP-FPM
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Configure Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache

# Set working directory
WORKDIR /var/www/html

# Copy application from php-deps stage
COPY --from=php-deps --chown=www-data:www-data /var/www/html .

# Copy built assets from node-builder stage
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create startup script
RUN echo '#!/bin/sh' > /start.sh && \
    echo 'php artisan config:cache' >> /start.sh && \
    echo 'php artisan route:cache' >> /start.sh && \
    echo 'php artisan view:cache' >> /start.sh && \
    echo 'php artisan migrate --force' >> /start.sh && \
    echo 'supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /start.sh && \
    chmod +x /start.sh

# Expose ports
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start services
CMD ["/start.sh"]