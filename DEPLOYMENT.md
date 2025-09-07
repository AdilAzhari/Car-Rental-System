# 🚀 Car Rental System - Deployment Guide

This guide covers deployment strategies for the Car Rental System in different environments.

## 📋 Prerequisites

### Server Requirements
- **PHP 8.4+** with required extensions
- **MySQL 8.0+** or **MariaDB 10.5+**
- **Web Server** (Apache 2.4+ or Nginx 1.18+)
- **Composer** for dependency management
- **Node.js 18+** and **NPM** for asset compilation
- **SSL Certificate** for production security

### PHP Extensions Required
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick (for image processing)

## 🌍 Environment Setup

### Production Environment (.env)
```env
APP_NAME="Car Rental System"
APP_ENV=production
APP_KEY=your-generated-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=your-production-database
DB_USERNAME=your-database-user
DB_PASSWORD=your-secure-password

CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Performance optimizations
OPCACHE_ENABLE=true
VIEW_CACHE=true
ROUTE_CACHE=true
CONFIG_CACHE=true
```

## 🏗 Deployment Methods

### Method 1: Manual Deployment

#### Step 1: Server Preparation
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install PHP 8.4
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.4-fpm php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-redis

# Install MySQL
sudo apt install mysql-server

# Install Redis
sudo apt install redis-server

# Install Nginx
sudo apt install nginx

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs
```

#### Step 2: Application Deployment
```bash
# Clone repository
cd /var/www
sudo git clone your-repository-url car-rental-system
cd car-rental-system

# Set permissions
sudo chown -R www-data:www-data /var/www/car-rental-system
sudo chmod -R 755 /var/www/car-rental-system/storage
sudo chmod -R 755 /var/www/car-rental-system/bootstrap/cache

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Setup environment
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Clear and cache configs
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### Method 2: Docker Deployment

#### Docker Compose Setup
```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    volumes:
      - ./storage:/var/www/html/storage

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secure_password
      MYSQL_DATABASE: car_rental_system
      MYSQL_USER: app_user
      MYSQL_PASSWORD: app_password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app

volumes:
  mysql_data:
```

#### Dockerfile
```dockerfile
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage
RUN chmod -R 755 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8000

# Start application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

## ⚙️ Server Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/car-rental-system/public;
    index index.php index.html;

    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private no_etag no_last_modified auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;
}
```

### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/car-rental-system/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key

    <Directory /var/www/car-rental-system/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000"

    # Compression
    LoadModule deflate_module modules/mod_deflate.so
    <Location />
        SetOutputFilter DEFLATE
        SetEnvIfNoCase Request_URI \
            \.(?:gif|jpe?g|png)$ no-gzip dont-vary
        SetEnvIfNoCase Request_URI \
            \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    </Location>
</VirtualHost>
```

## 📊 Performance Optimization

### Database Optimization
```sql
-- Create indexes for better performance
CREATE INDEX idx_bookings_dates ON car_rental_bookings(start_date, end_date);
CREATE INDEX idx_bookings_status ON car_rental_bookings(status);
CREATE INDEX idx_vehicles_status ON car_rental_vehicles(status);
CREATE INDEX idx_payments_status ON car_rental_payments(payment_status);
```

### Laravel Optimizations
```bash
# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Queue processing (recommended)
php artisan queue:work --daemon --sleep=3 --tries=3

# Schedule runner (add to crontab)
* * * * * cd /var/www/car-rental-system && php artisan schedule:run >> /dev/null 2>&1
```

### Redis Configuration
```bash
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## 🔧 Maintenance

### Backup Strategy
```bash
#!/bin/bash
# backup.sh - Daily backup script

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/car-rental-system"
APP_DIR="/var/www/car-rental-system"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u username -p password car_rental_system > $BACKUP_DIR/database_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $APP_DIR storage public/uploads

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### Update Deployment
```bash
#!/bin/bash
# deploy.sh - Deployment script

cd /var/www/car-rental-system

# Enable maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# Disable maintenance mode
php artisan up
```

## 🔐 Security Checklist

### SSL/TLS Configuration
- ✅ Valid SSL certificate installed
- ✅ HTTP to HTTPS redirect configured
- ✅ HSTS headers implemented
- ✅ Strong SSL ciphers configured

### Application Security
- ✅ APP_DEBUG=false in production
- ✅ Strong APP_KEY generated
- ✅ Database credentials secured
- ✅ File permissions properly set (755 for directories, 644 for files)
- ✅ Storage and cache directories writable by web server

### Server Security
- ✅ Firewall configured (ports 80, 443, 22 only)
- ✅ Regular security updates applied
- ✅ Non-root user for application
- ✅ Database access restricted
- ✅ Backup strategy implemented

## 📈 Monitoring

### Health Check Endpoints
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('health_check') ? 'working' : 'failed'
    ]);
});
```

### Log Monitoring
```bash
# Monitor application logs
tail -f /var/www/car-rental-system/storage/logs/laravel.log

# Monitor Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

## 🚨 Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check file permissions
- Verify .env configuration
- Check application logs
- Ensure storage directory is writable

**Database Connection Issues**
- Verify database credentials
- Check database server status
- Confirm database exists
- Test connection from server

**Asset Loading Issues**
- Run `npm run build`
- Check file permissions
- Verify asset paths in configuration

**Performance Issues**
- Enable caching (config, routes, views)
- Configure Redis
- Optimize database queries
- Review server resources

For additional support, check the main README.md file or create an issue in the repository.