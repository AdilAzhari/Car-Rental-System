# 🚀 SENTIENTS A.I Car Rental System - Deployment Guide

## 📋 Prerequisites

### Server Requirements
- **PHP**: 8.4+ with extensions: `bcmath`, `curl`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Web Server**: Nginx (recommended) or Apache
- **Memory**: Minimum 512MB RAM (1GB+ recommended)
- **Storage**: At least 1GB free space
- **SSL Certificate**: For HTTPS (required for production)

### Local Requirements (for deployment)
- Git
- Composer
- Node.js 18+
- npm or yarn

---

## 🔧 Phase 1: Basic Deployment (No Redis)

### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.4 and extensions
sudo apt install php8.4 php8.4-fpm php8.4-mysql php8.4-xml php8.4-curl php8.4-zip php8.4-mbstring php8.4-bcmath php8.4-gd -y

# Install MySQL
sudo apt install mysql-server -y

# Install Nginx
sudo apt install nginx -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### 2. Project Deployment

```bash
# Clone your repository
git clone https://github.com/yourusername/CarRentSystem.git
cd CarRentSystem

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies and build assets
npm install
npm run build

# Set up environment
cp .env.production .env
nano .env  # Edit with your production settings

# Generate application key
php artisan key:generate

# Set proper permissions
sudo chown -R www-data:www-data storage/ bootstrap/cache/
sudo chmod -R 775 storage/ bootstrap/cache/
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE your_production_db;
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON your_production_db.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --force
php artisan db:seed --force
```

### 4. Optimization

```bash
# Run all optimizations
php artisan app:optimize-performance

# Additional production optimizations
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Web Server Configuration

#### Nginx Configuration

Create `/etc/nginx/sites-available/sentients-ai`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/CarRentSystem/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/sentients-ai /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🏃‍♂️ Phase 2: When to Scale (Add Redis)

### When You Need Redis:
- ✅ **50+ concurrent users**
- ✅ **Multiple server instances**
- ✅ **High cache hit requirements**
- ✅ **Real-time features needed**

### Redis Installation:

```bash
# Install Redis
sudo apt install redis-server -y

# Configure Redis
sudo nano /etc/redis/redis.conf
# Set: maxmemory 256mb
# Set: maxmemory-policy allkeys-lru

# Start Redis
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Test Redis
redis-cli ping  # Should return PONG
```

### Update Environment for Redis:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 📊 Monitoring & Maintenance

### Performance Monitoring

```bash
# Check application performance
php artisan app:optimize-performance

# Monitor logs
tail -f storage/logs/laravel.log

# Check database performance
mysql -u root -p -e "SHOW PROCESSLIST;"

# Monitor server resources
htop
df -h
```

### Regular Maintenance

```bash
# Weekly cache optimization
php artisan cache:clear
php artisan app:optimize-performance

# Monthly log cleanup
php artisan log:clear

# Database optimization
php artisan db:optimize
```

---

## 🔒 Security Checklist

- [ ] **HTTPS enabled** with valid SSL certificate
- [ ] **Firewall configured** (UFW or iptables)
- [ ] **Database user** has minimal required permissions
- [ ] **File permissions** are correctly set (775 for storage)
- [ ] **APP_DEBUG=false** in production
- [ ] **APP_KEY** is generated and secure
- [ ] **Backup strategy** implemented
- [ ] **Log monitoring** configured
- [ ] **Regular updates** scheduled

---

## 🚨 Troubleshooting

### Common Issues:

**500 Internal Server Error:**
```bash
# Check permissions
sudo chown -R www-data:www-data storage/ bootstrap/cache/

# Check logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

**Database Connection Issues:**
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

**Cache Issues:**
```bash
# Clear all caches
php artisan optimize:clear
php artisan app:optimize-performance
```

---

## 📞 Support

For deployment assistance or issues:
- Check logs: `storage/logs/laravel.log`
- Run health check: `php artisan health`
- Performance check: `php artisan app:optimize-performance`

**Key Point**: Your application is already optimized for production deployment without Redis. You can deploy immediately and add Redis later when you have significant traffic!