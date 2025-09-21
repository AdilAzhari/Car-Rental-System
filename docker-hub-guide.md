# Docker Hub Deployment Guide

## Prerequisites
- Docker Desktop running
- Docker Hub account
- Built Docker image (takes time due to network)

## Step 1: Build Image (Patient Approach)
```bash
# This will take 15-30 minutes due to network
docker build -t car-rental-system:latest .
```

## Step 2: Tag for Docker Hub
```bash
# Replace 'yourusername' with your Docker Hub username
docker tag car-rental-system:latest yourusername/car-rental-system:latest
docker tag car-rental-system:latest yourusername/car-rental-system:v1.0
```

## Step 3: Login and Push
```bash
# Login to Docker Hub
docker login

# Push images
docker push yourusername/car-rental-system:latest
docker push yourusername/car-rental-system:v1.0
```

## Step 4: Deploy from Hub
```bash
# Set environment variables
set DOCKERHUB_USERNAME=yourusername
set IMAGE_TAG=latest
set DB_PASSWORD=secure_password
set REDIS_PASSWORD=secure_redis_password

# Deploy using Hub image
docker-compose -f docker-compose.hub.yml up -d
```

## Alternative: Use Pre-built Laravel Image
```bash
# Quick start with existing Laravel image
docker run -d \
  --name car-rental-app \
  -p 8000:80 \
  -v ${PWD}:/var/www/html \
  -e DB_HOST=host.docker.internal \
  -e DB_DATABASE=car_rental_system \
  -e DB_USERNAME=root \
  -e DB_PASSWORD= \
  serversideup/php:8.4-fpm-nginx
```

## Current Status
- Docker is working but network is slow
- Best approach: Use Laragon setup for development
- Use Docker for production deployment later