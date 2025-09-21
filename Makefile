# Car Rental System - Docker Management Makefile

.PHONY: help build up down restart logs shell test clean

# Default environment
ENV ?= development

# Color codes for output
GREEN := \033[0;32m
YELLOW := \033[1;33m
RED := \033[0;31m
NC := \033[0m

help: ## Show this help message
	@echo "$(GREEN)Car Rental System - Docker Commands$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make $(YELLOW)<target>$(NC)\n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

build: ## Build Docker images
	@echo "$(YELLOW)🔨 Building Docker images...$(NC)"
	docker-compose build --no-cache

up: ## Start all services
	@echo "$(YELLOW)🚀 Starting all services...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✅ Services started!$(NC)"
	@echo "Application: http://localhost:8000"
	@echo "Admin Panel: http://localhost:8000/admin"
	@echo "MailHog: http://localhost:8025"

down: ## Stop all services
	@echo "$(YELLOW)🛑 Stopping all services...$(NC)"
	docker-compose down

restart: ## Restart all services
	@echo "$(YELLOW)🔄 Restarting all services...$(NC)"
	docker-compose restart

logs: ## View logs for all services
	docker-compose logs -f

logs-app: ## View application logs
	docker-compose logs -f app

logs-db: ## View database logs
	docker-compose logs -f database

shell: ## Access application shell
	docker-compose exec app bash

shell-db: ## Access database shell
	docker-compose exec database mysql -u root -p

install: ## Install/Update dependencies
	@echo "$(YELLOW)📦 Installing dependencies...$(NC)"
	docker-compose exec app composer install
	docker-compose exec app npm install

fresh: ## Fresh installation (destroy data)
	@echo "$(RED)⚠️  This will destroy all data! Continue? [y/N]$(NC)" && read ans && [ $${ans:-N} = y ]
	docker-compose down -v
	docker-compose up -d
	sleep 30
	docker-compose exec app php artisan migrate:fresh --seed

migrate: ## Run database migrations
	@echo "$(YELLOW)🗄️  Running migrations...$(NC)"
	docker-compose exec app php artisan migrate

seed: ## Seed database
	@echo "$(YELLOW)🌱 Seeding database...$(NC)"
	docker-compose exec app php artisan db:seed

test: ## Run tests
	@echo "$(YELLOW)🧪 Running tests...$(NC)"
	docker-compose exec app php artisan test

test-coverage: ## Run tests with coverage
	@echo "$(YELLOW)🧪 Running tests with coverage...$(NC)"
	docker-compose exec app php artisan test --coverage

lint: ## Run code style fixer
	@echo "$(YELLOW)🔍 Running code style fixer...$(NC)"
	docker-compose exec app ./vendor/bin/pint

clear-cache: ## Clear application cache
	@echo "$(YELLOW)🧹 Clearing cache...$(NC)"
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

optimize: ## Optimize application for production
	@echo "$(YELLOW)⚡ Optimizing application...$(NC)"
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache
	docker-compose exec app composer dump-autoload --optimize

backup-db: ## Backup database
	@echo "$(YELLOW)💾 Creating database backup...$(NC)"
	docker-compose exec database mysqldump -u root -p car_rental_system > backup_$(shell date +%Y%m%d_%H%M%S).sql

status: ## Show service status
	@echo "$(YELLOW)📊 Service Status:$(NC)"
	docker-compose ps

health: ## Check application health
	@echo "$(YELLOW)🏥 Checking application health...$(NC)"
	@if curl -f http://localhost:8000/health > /dev/null 2>&1; then \
		echo "$(GREEN)✅ Application is healthy!$(NC)"; \
	else \
		echo "$(RED)❌ Application health check failed$(NC)"; \
	fi

clean: ## Clean up Docker resources
	@echo "$(YELLOW)🧹 Cleaning up Docker resources...$(NC)"
	docker system prune -f
	docker volume prune -f

clean-all: ## Clean up everything (DANGER: removes all data)
	@echo "$(RED)⚠️  This will remove all containers, volumes, and images! Continue? [y/N]$(NC)" && read ans && [ $${ans:-N} = y ]
	docker-compose down -v --rmi all
	docker system prune -a -f

# Production commands
prod-build: ## Build production images
	@echo "$(YELLOW)🏭 Building production images...$(NC)"
	docker-compose -f docker-compose.prod.yml build --no-cache

prod-up: ## Start production services
	@echo "$(YELLOW)🚀 Starting production services...$(NC)"
	docker-compose -f docker-compose.prod.yml up -d

prod-down: ## Stop production services
	@echo "$(YELLOW)🛑 Stopping production services...$(NC)"
	docker-compose -f docker-compose.prod.yml down

# Development shortcuts
dev: build up migrate seed ## Full development setup

quick: ## Quick start (assumes images are built)
	docker-compose up -d

stop: down ## Alias for down

# Default target
.DEFAULT_GOAL := help