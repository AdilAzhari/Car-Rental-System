#!/bin/bash

# Docker Build and Push Script for Car Rental System
# Usage: ./docker-publish.sh [version] [dockerhub-username]

set -e

VERSION=${1:-latest}
DOCKERHUB_USERNAME=${2:-your-username}
IMAGE_NAME="car-rental-system"
FULL_IMAGE_NAME="$DOCKERHUB_USERNAME/$IMAGE_NAME"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}🐳 Car Rental System - Docker Build & Push${NC}"
echo "=========================================="
echo "Version: $VERSION"
echo "Docker Hub Username: $DOCKERHUB_USERNAME"
echo "Full Image Name: $FULL_IMAGE_NAME:$VERSION"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running. Please start Docker first.${NC}"
    exit 1
fi

# Build the image
echo -e "${YELLOW}🔨 Building Docker image...${NC}"
docker build -t $IMAGE_NAME:$VERSION -t $IMAGE_NAME:latest .

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Docker build failed!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker image built successfully!${NC}"

# Tag for Docker Hub
echo -e "${YELLOW}🏷️  Tagging image for Docker Hub...${NC}"
docker tag $IMAGE_NAME:$VERSION $FULL_IMAGE_NAME:$VERSION
docker tag $IMAGE_NAME:latest $FULL_IMAGE_NAME:latest

# Login check
echo -e "${YELLOW}🔐 Checking Docker Hub login...${NC}"
if ! docker info | grep -q "Username:"; then
    echo -e "${YELLOW}⚠️  Not logged in to Docker Hub. Please login:${NC}"
    docker login
fi

# Push to Docker Hub
echo -e "${YELLOW}🚀 Pushing to Docker Hub...${NC}"
docker push $FULL_IMAGE_NAME:$VERSION
docker push $FULL_IMAGE_NAME:latest

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Successfully pushed to Docker Hub!${NC}"
    echo ""
    echo -e "${YELLOW}📋 Image Details:${NC}"
    echo "• Repository: https://hub.docker.com/r/$DOCKERHUB_USERNAME/$IMAGE_NAME"
    echo "• Pull command: docker pull $FULL_IMAGE_NAME:$VERSION"
    echo "• Run command: docker run -d -p 8000:80 $FULL_IMAGE_NAME:$VERSION"
    echo ""
    echo -e "${YELLOW}🏗️  Production Deployment:${NC}"
    echo "• Update docker-compose.prod.yml to use: $FULL_IMAGE_NAME:$VERSION"
    echo "• Or deploy directly: docker run -d -p 80:80 $FULL_IMAGE_NAME:$VERSION"
else
    echo -e "${RED}❌ Failed to push to Docker Hub!${NC}"
    exit 1
fi

# Clean up local images (optional)
read -p "Do you want to clean up local build images? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}🧹 Cleaning up local images...${NC}"
    docker image prune -f
    echo -e "${GREEN}✅ Cleanup completed!${NC}"
fi

echo -e "${GREEN}🎉 Docker publish workflow completed successfully!${NC}"