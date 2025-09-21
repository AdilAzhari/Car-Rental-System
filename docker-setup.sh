#!/bin/bash

# Docker Setup Script for Car Rental System
# Usage: ./docker-setup.sh [development|production]

set -e

ENVIRONMENT=${1:-development}
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Setting up Car Rental System with Docker${NC}"
echo -e "${YELLOW}Environment: $ENVIRONMENT${NC}"

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
echo -e "${YELLOW}📋 Checking prerequisites...${NC}"

if ! command_exists docker; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

if ! command_exists docker-compose; then
    echo -e "${RED}❌ Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Prerequisites check passed${NC}"

# Create necessary directories
echo -e "${YELLOW}📁 Creating necessary directories...${NC}"
mkdir -p docker/nginx docker/php docker/supervisor docker/mysql docker/ssl storage/logs

# Copy environment file
if [ ! -f .env ]; then
    echo -e "${YELLOW}📄 Creating .env file...${NC}"
    cp .env.example .env

    # Generate application key
    echo -e "${YELLOW}🔑 Generating application key...${NC}"
    if command_exists php; then
        php artisan key:generate
    else
        echo -e "${YELLOW}⚠️  PHP not found locally. Application key will be generated in container.${NC}"
    fi
fi

# Setup based on environment
if [ "$ENVIRONMENT" = "production" ]; then
    echo -e "${YELLOW}🏭 Setting up production environment...${NC}"

    # Check for required environment variables
    if [ -z "$DB_PASSWORD" ] || [ -z "$REDIS_PASSWORD" ]; then
        echo -e "${RED}❌ Production requires DB_PASSWORD and REDIS_PASSWORD environment variables${NC}"
        echo "Please set them and run again:"
        echo "export DB_PASSWORD=your_secure_password"
        echo "export REDIS_PASSWORD=your_redis_password"
        exit 1
    fi

    # Build and start production containers
    docker-compose -f docker-compose.prod.yml build --no-cache
    docker-compose -f docker-compose.prod.yml up -d

    COMPOSE_FILE="docker-compose.prod.yml"
else
    echo -e "${YELLOW}🔧 Setting up development environment...${NC}"

    # Build and start development containers
    docker-compose build --no-cache
    docker-compose up -d

    COMPOSE_FILE="docker-compose.yml"
fi

# Wait for services to be ready
echo -e "${YELLOW}⏳ Waiting for services to be ready...${NC}"
sleep 30

# Run Laravel setup commands
echo -e "${YELLOW}🔧 Running Laravel setup commands...${NC}"

# Generate key if not done locally
docker-compose -f $COMPOSE_FILE exec app php artisan key:generate --force

# Run migrations
echo -e "${YELLOW}🗄️  Running database migrations...${NC}"
docker-compose -f $COMPOSE_FILE exec app php artisan migrate --force

# Seed database (development only)
if [ "$ENVIRONMENT" = "development" ]; then
    echo -e "${YELLOW}🌱 Seeding database...${NC}"
    docker-compose -f $COMPOSE_FILE exec app php artisan db:seed --force
fi

# Clear and cache configuration
echo -e "${YELLOW}🧹 Optimizing application...${NC}"
docker-compose -f $COMPOSE_FILE exec app php artisan config:cache
docker-compose -f $COMPOSE_FILE exec app php artisan route:cache
docker-compose -f $COMPOSE_FILE exec app php artisan view:cache

# Set proper permissions
echo -e "${YELLOW}🔐 Setting permissions...${NC}"
docker-compose -f $COMPOSE_FILE exec app chown -R www-data:www-data /var/www/html/storage
docker-compose -f $COMPOSE_FILE exec app chmod -R 775 /var/www/html/storage

# Health check
echo -e "${YELLOW}🏥 Running health checks...${NC}"
sleep 10

if curl -f http://localhost:8000/health >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Application is healthy!${NC}"
else
    echo -e "${RED}❌ Health check failed. Check logs with: docker-compose -f $COMPOSE_FILE logs${NC}"
fi

# Display information
echo -e "${GREEN}🎉 Setup complete!${NC}"
echo ""
echo -e "${YELLOW}📊 Service Information:${NC}"
echo "• Application: http://localhost:8000"
echo "• Admin Panel: http://localhost:8000/admin"

if [ "$ENVIRONMENT" = "development" ]; then
    echo "• MailHog: http://localhost:8025"
    echo "• Database: localhost:3306"
    echo "• Redis: localhost:6379"
fi

echo ""
echo -e "${YELLOW}🔧 Useful Commands:${NC}"
echo "• View logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "• Stop services: docker-compose -f $COMPOSE_FILE down"
echo "• Restart services: docker-compose -f $COMPOSE_FILE restart"
echo "• Execute commands: docker-compose -f $COMPOSE_FILE exec app php artisan [command]"

echo ""
echo -e "${GREEN}🚀 Your Car Rental System is now running with Docker!${NC}"