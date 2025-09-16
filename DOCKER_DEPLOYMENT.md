# Docker Deployment Guide for Car Rental System

## Overview

This Laravel Car Rental System is containerized using Docker with a production-optimized multi-stage build process. The setup includes:

- **PHP 8.4 with FPM** - Latest PHP version with FastCGI Process Manager
- **Nginx** - High-performance web server
- **MySQL 8.0** - Database server
- **Redis** - Caching and session storage
- **Supervisor** - Process management for queues and scheduler
- **Multi-stage builds** - Optimized image size (~150MB final image)

## Architecture Explanation

### Multi-Stage Build Process

The Dockerfile uses three stages to optimize the final image:

1. **Stage 1: PHP Dependencies (`php-deps`)**
   - Installs system dependencies and PHP extensions
   - Installs Composer and Laravel dependencies
   - Runs composer optimizations for production
   - Caches dependencies for faster rebuilds

2. **Stage 2: Node Assets (`node-builder`)**
   - Builds frontend assets (Vue.js, CSS, JavaScript)
   - Runs npm build for production-optimized assets
   - Generates minified and bundled files

3. **Stage 3: Production Image (`production`)**
   - Contains only runtime dependencies (no build tools)
   - Copies built artifacts from previous stages
   - Configures Nginx, PHP-FPM, and Supervisor
   - Sets up health checks and startup scripts

### Key Components

#### PHP-FPM Configuration
- **Dynamic process management** - Scales workers based on load
- **Optimized memory limits** - 256MB per process
- **Request timeouts** - 30 seconds with slow log tracking
- **Unix socket** - Better performance than TCP

#### Nginx Configuration
- **Gzip compression** - Reduces bandwidth usage
- **Static file caching** - 30-day cache for assets
- **Rate limiting** - Protection against abuse
- **Security headers** - XSS, clickjacking protection
- **FastCGI caching** - Improved PHP response times

#### Supervisor Processes
- **PHP-FPM** - Manages PHP processes
- **Nginx** - Web server process
- **Queue Workers** - 2 workers for background jobs
- **Scheduler** - Runs Laravel scheduled tasks

#### Security Features
- **Non-root user** - Runs as www-data
- **Hidden files protection** - Denies access to .env, .git
- **PHP security** - Disabled dangerous functions
- **HTTPS ready** - SSL/TLS configuration included
- **Rate limiting** - API and auth endpoints protected

## Quick Start

### Prerequisites
- Docker Engine 20.10+
- Docker Compose 2.0+
- 2GB RAM minimum
- 10GB disk space

### Development Deployment

```bash
# Clone the repository
git clone <repository-url>
cd CarRentSystem

# Copy environment file
cp .env.example .env

# Generate application key
docker run --rm -v $(pwd):/app composer install
docker run --rm -v $(pwd):/app php artisan key:generate

# Start services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --seed

# Access the application
# http://localhost
```

### Production Deployment

```bash
# Build production image
docker build -t carrental:latest --target production .

# Run with docker-compose
docker-compose -f docker-compose.yml up -d

# Or run standalone
docker run -d \
  --name carrental \
  -p 80:80 \
  -p 443:443 \
  -e APP_KEY=your-app-key \
  -e DB_HOST=your-db-host \
  -e DB_PASSWORD=your-db-password \
  carrental:latest
```

## Environment Variables

Key environment variables for production:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=carrental
DB_USERNAME=carrental
DB_PASSWORD=strong-password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password

# Payment
STRIPE_KEY=your-stripe-key
STRIPE_SECRET=your-stripe-secret
```

## Docker Commands

### Build Commands
```bash
# Build for production
docker build -t carrental:prod --target production .

# Build with specific PHP version
docker build --build-arg PHP_VERSION=8.3 -t carrental:php83 .

# Build with cache
docker build --cache-from carrental:latest -t carrental:new .
```

### Container Management
```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f app

# Execute commands in container
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan queue:restart

# Access container shell
docker-compose exec app sh
```

### Database Operations
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Backup database
docker-compose exec db mysqldump -u root -p carrental > backup.sql

# Restore database
docker-compose exec -T db mysql -u root -p carrental < backup.sql
```

## Performance Optimization

### Image Size Optimization
- Alpine Linux base (5MB)
- Multi-stage builds
- Only production dependencies
- No development tools in final image
- Result: ~150MB final image

### Runtime Optimization
- OPcache enabled with preloading
- Redis for caching and sessions
- Nginx static file caching
- Gzip compression
- FastCGI buffering

### Database Optimization
- Query caching enabled
- Indexed columns
- Eager loading for relationships
- Connection pooling

## Monitoring & Health Checks

### Health Check Endpoint
```bash
# Check application health
curl http://localhost/health
```

### Container Health
```bash
# Check container status
docker-compose ps

# View resource usage
docker stats carrental-app
```

### Logs
- Application logs: `/var/log/php/`
- Nginx logs: `/var/log/nginx/`
- Supervisor logs: `/var/log/supervisor/`

## Troubleshooting

### Common Issues

1. **Permission Issues**
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

2. **Cache Issues**
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
```

3. **Database Connection**
```bash
# Test database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

4. **Queue Issues**
```bash
docker-compose exec app php artisan queue:restart
docker-compose exec app supervisorctl restart laravel-worker:*
```

## Security Considerations

1. **Use secrets management** for sensitive environment variables
2. **Enable HTTPS** in production with proper certificates
3. **Regular updates** of base images and dependencies
4. **Network isolation** using Docker networks
5. **Resource limits** to prevent DoS attacks
6. **Log rotation** to prevent disk space issues
7. **Backup strategy** for database and uploads

## Scaling

### Horizontal Scaling
```yaml
# docker-compose.yml
services:
  app:
    deploy:
      replicas: 3
```

### Load Balancing
Add a load balancer service:
```yaml
  nginx-lb:
    image: nginx:alpine
    volumes:
      - ./docker/nginx/lb.conf:/etc/nginx/nginx.conf
    ports:
      - "80:80"
    depends_on:
      - app
```

## CI/CD Integration

### GitHub Actions Example
```yaml
name: Deploy
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Build and push
        run: |
          docker build -t carrental:${{ github.sha }} .
          docker push carrental:${{ github.sha }}
```

## Support

For issues or questions:
1. Check application logs: `docker-compose logs app`
2. Review this documentation
3. Check Laravel logs: `storage/logs/laravel.log`
4. Submit issues to the repository