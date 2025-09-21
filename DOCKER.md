# 🐳 Docker Setup Guide

This guide will help you set up the Car Rental System using Docker for both development and production environments.

## 📋 Prerequisites

- **Docker** (version 20.10 or higher)
- **Docker Compose** (version 2.0 or higher)
- **Git** (for cloning the repository)

### Install Docker

#### Windows & Mac
Download and install [Docker Desktop](https://www.docker.com/products/docker-desktop/)

#### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install docker.io docker-compose
sudo usermod -aG docker $USER
```

## 🚀 Quick Start

### Development Environment

1. **Clone the repository**
   ```bash
   git clone https://github.com/AdilAzhari/Car-Rental-System.git
   cd Car-Rental-System
   ```

2. **Quick setup with script**
   ```bash
   chmod +x docker-setup.sh
   ./docker-setup.sh development
   ```

3. **Or use Makefile commands**
   ```bash
   make dev  # Full development setup
   ```

4. **Access the application**
   - Application: http://localhost:8000
   - Admin Panel: http://localhost:8000/admin
   - MailHog: http://localhost:8025

### Production Environment

1. **Set environment variables**
   ```bash
   export DB_PASSWORD=your_secure_password
   export REDIS_PASSWORD=your_redis_password
   ```

2. **Deploy to production**
   ```bash
   ./docker-setup.sh production
   ```

## 🛠️ Available Commands

### Using Makefile (Recommended)

```bash
# Development
make up          # Start all services
make down        # Stop all services
make restart     # Restart all services
make logs        # View logs
make shell       # Access app shell

# Database
make migrate     # Run migrations
make seed        # Seed database
make fresh       # Fresh install (destroys data)

# Testing & Quality
make test        # Run tests
make lint        # Run code style fixer
make optimize    # Optimize for production

# Production
make prod-up     # Start production services
make prod-down   # Stop production services
```

### Using Docker Compose Directly

```bash
# Development
docker-compose up -d
docker-compose down
docker-compose logs -f

# Production
docker-compose -f docker-compose.prod.yml up -d
docker-compose -f docker-compose.prod.yml down
```

## 📊 Service Overview

### Development Services

| Service | Port | Description |
|---------|------|-------------|
| **app** | 8000 | Main Laravel application |
| **database** | 3306 | MySQL 8.0 database |
| **redis** | 6379 | Redis cache & sessions |
| **queue** | - | Queue worker |
| **scheduler** | - | Laravel scheduler |
| **mailhog** | 8025/1025 | Email testing |

### Production Services

| Service | Port | Description |
|---------|------|-------------|
| **app** | - | Laravel app (behind nginx) |
| **nginx** | 80/443 | Web server & proxy |
| **database** | - | MySQL (internal only) |
| **redis** | - | Redis (internal only) |
| **queue** | - | Queue worker |

## 🔧 Configuration

### Environment Variables

Create a `.env` file or set these environment variables:

```env
# Database
DB_HOST=database
DB_DATABASE=car_rental_system
DB_USERNAME=car_rental_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Application
APP_URL=http://localhost:8000
APP_CURRENCY=MYR
APP_CURRENCY_SYMBOL=RM
```

### Custom Configuration

1. **PHP Settings**: Edit `docker/php/php.ini`
2. **Nginx Settings**: Edit `docker/nginx/default.conf`
3. **MySQL Settings**: Edit `docker/mysql/init.sql`

## 🗄️ Database Management

### Access Database

```bash
# Using Makefile
make shell-db

# Or directly
docker-compose exec database mysql -u root -p
```

### Backup Database

```bash
# Using Makefile
make backup-db

# Or directly
docker-compose exec database mysqldump -u root -p car_rental_system > backup.sql
```

### Restore Database

```bash
docker-compose exec -T database mysql -u root -p car_rental_system < backup.sql
```

## 🧪 Testing

### Run Tests

```bash
# Using Makefile
make test
make test-coverage

# Or directly
docker-compose exec app php artisan test
```

### Run Code Quality Checks

```bash
# Code style
make lint

# Or directly
docker-compose exec app ./vendor/bin/pint
```

## 🔍 Debugging

### View Logs

```bash
# All services
make logs

# Specific service
make logs-app
make logs-db

# Or directly
docker-compose logs -f app
```

### Access Application Shell

```bash
# Using Makefile
make shell

# Or directly
docker-compose exec app bash
```

### Health Checks

```bash
# Check application health
make health

# Or directly
curl http://localhost:8000/health
```

## 📈 Performance Optimization

### Production Optimizations

1. **Enable OPcache** (already configured)
2. **Use Redis for caching** (already configured)
3. **Optimize Composer autoloader**
   ```bash
   docker-compose exec app composer dump-autoload --optimize
   ```

### Monitoring

```bash
# Container stats
docker stats

# Service status
make status
```

## 🛡️ Security

### Production Security

1. **Change default passwords** in environment variables
2. **Use HTTPS** with proper SSL certificates
3. **Limit container privileges**
4. **Regular security updates**

### SSL Setup

1. Place SSL certificates in `docker/ssl/`
2. Update nginx configuration
3. Restart services

## 🚨 Troubleshooting

### Common Issues

#### Port Already in Use
```bash
# Check what's using the port
lsof -i :8000
# or
netstat -tulpn | grep 8000
```

#### Permission Issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chmod -R 775 storage
```

#### Database Connection Issues
```bash
# Check database status
docker-compose exec database mysqladmin ping -h localhost
```

#### Clear Everything and Restart
```bash
make clean-all  # ⚠️ This removes all data!
make dev
```

### Debug Mode

Enable debug mode temporarily:
```bash
docker-compose exec app php artisan tinker
```

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Laravel Docker Guide](https://laravel.com/docs/deployment#docker)
- [Docker Compose Reference](https://docs.docker.com/compose/)

## 🤝 Contributing

When contributing, please ensure:
1. Docker setup works for both development and production
2. All tests pass in containers
3. Documentation is updated

## 📞 Support

If you encounter issues:
1. Check this documentation
2. Review container logs: `make logs`
3. Open an issue on GitHub with logs and system info