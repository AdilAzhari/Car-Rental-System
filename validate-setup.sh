#!/bin/bash

# Validation Script for Car Rental System
# This script validates that all components are working correctly

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}🔍 Car Rental System - Setup Validation${NC}"
echo "=================================================="

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to test HTTP endpoint
test_endpoint() {
    local url=$1
    local description=$2

    if curl -f -s "$url" > /dev/null; then
        echo -e "${GREEN}✅ $description: OK${NC}"
        return 0
    else
        echo -e "${RED}❌ $description: FAILED${NC}"
        return 1
    fi
}

# Function to test service health
test_service() {
    local service=$1
    local description=$2

    if docker-compose ps "$service" | grep -q "Up"; then
        echo -e "${GREEN}✅ $description: Running${NC}"
        return 0
    else
        echo -e "${RED}❌ $description: Not running${NC}"
        return 1
    fi
}

FAILED_TESTS=0

echo -e "\n${YELLOW}📋 Prerequisites Check${NC}"
echo "----------------------------------------"

# Check Docker
if command_exists docker; then
    DOCKER_VERSION=$(docker --version | cut -d' ' -f3 | cut -d',' -f1)
    echo -e "${GREEN}✅ Docker: $DOCKER_VERSION${NC}"
else
    echo -e "${RED}❌ Docker: Not installed${NC}"
    ((FAILED_TESTS++))
fi

# Check Docker Compose
if command_exists docker-compose; then
    COMPOSE_VERSION=$(docker-compose --version | cut -d' ' -f3 | cut -d',' -f1)
    echo -e "${GREEN}✅ Docker Compose: $COMPOSE_VERSION${NC}"
else
    echo -e "${RED}❌ Docker Compose: Not installed${NC}"
    ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}🐳 Docker Services Check${NC}"
echo "----------------------------------------"

# Check if services are running
test_service "app" "Laravel Application" || ((FAILED_TESTS++))
test_service "database" "MySQL Database" || ((FAILED_TESTS++))
test_service "redis" "Redis Cache" || ((FAILED_TESTS++))
test_service "queue" "Queue Worker" || ((FAILED_TESTS++))

echo -e "\n${YELLOW}🌐 HTTP Endpoints Check${NC}"
echo "----------------------------------------"

# Test main endpoints
test_endpoint "http://localhost:8000/health" "Health Check" || ((FAILED_TESTS++))
test_endpoint "http://localhost:8000" "Homepage" || ((FAILED_TESTS++))
test_endpoint "http://localhost:8000/admin/login" "Admin Login" || ((FAILED_TESTS++))

# Test MailHog (development only)
if docker-compose ps mailhog 2>/dev/null | grep -q "Up"; then
    test_endpoint "http://localhost:8025" "MailHog Interface" || ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}🗄️ Database Connectivity${NC}"
echo "----------------------------------------"

# Test database connection
if docker-compose exec -T database mysqladmin ping -h localhost --silent; then
    echo -e "${GREEN}✅ Database: Connection OK${NC}"
else
    echo -e "${RED}❌ Database: Connection failed${NC}"
    ((FAILED_TESTS++))
fi

# Test Redis connection
if docker-compose exec -T redis redis-cli ping | grep -q "PONG"; then
    echo -e "${GREEN}✅ Redis: Connection OK${NC}"
else
    echo -e "${RED}❌ Redis: Connection failed${NC}"
    ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}🧪 Laravel Application Check${NC}"
echo "----------------------------------------"

# Test Laravel key
if docker-compose exec -T app php artisan config:show app.key | grep -q "base64:"; then
    echo -e "${GREEN}✅ Laravel: Application key configured${NC}"
else
    echo -e "${RED}❌ Laravel: Application key missing${NC}"
    ((FAILED_TESTS++))
fi

# Test database migrations
MIGRATION_COUNT=$(docker-compose exec -T app php artisan migrate:status | grep -c "Ran" || echo "0")
if [ "$MIGRATION_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✅ Laravel: Database migrations ($MIGRATION_COUNT)${NC}"
else
    echo -e "${RED}❌ Laravel: No migrations found${NC}"
    ((FAILED_TESTS++))
fi

# Test storage permissions
if docker-compose exec -T app test -w /var/www/html/storage; then
    echo -e "${GREEN}✅ Laravel: Storage permissions OK${NC}"
else
    echo -e "${RED}❌ Laravel: Storage not writable${NC}"
    ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}🔧 Configuration Check${NC}"
echo "----------------------------------------"

# Check environment file
if [ -f .env ]; then
    echo -e "${GREEN}✅ Environment: .env file exists${NC}"
else
    echo -e "${RED}❌ Environment: .env file missing${NC}"
    ((FAILED_TESTS++))
fi

# Check currency configuration
CURRENCY=$(docker-compose exec -T app php artisan config:show app.currency 2>/dev/null || echo "")
if [ "$CURRENCY" = "MYR" ]; then
    echo -e "${GREEN}✅ Configuration: Currency set to MYR${NC}"
else
    echo -e "${YELLOW}⚠️  Configuration: Currency is $CURRENCY (expected MYR)${NC}"
fi

echo -e "\n${YELLOW}📊 Performance Check${NC}"
echo "----------------------------------------"

# Check container resource usage
echo "Docker container resource usage:"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}"

echo -e "\n${YELLOW}🔍 GitHub Actions Validation${NC}"
echo "----------------------------------------"

# Check GitHub Actions files
if [ -f .github/workflows/ci.yml ]; then
    echo -e "${GREEN}✅ CI/CD: GitHub Actions workflow configured${NC}"
else
    echo -e "${RED}❌ CI/CD: GitHub Actions workflow missing${NC}"
    ((FAILED_TESTS++))
fi

if [ -f .github/workflows/screenshots.yml ]; then
    echo -e "${GREEN}✅ Screenshots: Automated screenshot workflow configured${NC}"
else
    echo -e "${RED}❌ Screenshots: Screenshot workflow missing${NC}"
    ((FAILED_TESTS++))
fi

echo -e "\n${YELLOW}🎨 Frontend Assets Check${NC}"
echo "----------------------------------------"

# Check if build files exist
if [ -d "public/build" ] && [ "$(ls -A public/build 2>/dev/null)" ]; then
    echo -e "${GREEN}✅ Frontend: Assets built and available${NC}"
else
    echo -e "${YELLOW}⚠️  Frontend: Assets not built (run 'npm run build')${NC}"
fi

echo -e "\n=================================================="

# Final summary
if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed! Your Car Rental System is ready to go!${NC}"
    echo ""
    echo -e "${YELLOW}Quick access links:${NC}"
    echo "• Application: http://localhost:8000"
    echo "• Admin Panel: http://localhost:8000/admin"
    echo "• MailHog: http://localhost:8025"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Access the admin panel and create your first admin user"
    echo "2. Configure your vehicle fleet"
    echo "3. Test the booking process"
    echo "4. Set up your production environment"
    exit 0
else
    echo -e "${RED}❌ $FAILED_TESTS test(s) failed. Please review the issues above.${NC}"
    echo ""
    echo -e "${YELLOW}Troubleshooting:${NC}"
    echo "• View logs: docker-compose logs -f"
    echo "• Restart services: docker-compose restart"
    echo "• Check Docker documentation: ./DOCKER.md"
    exit 1
fi