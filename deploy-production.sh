#!/bin/bash

# SarvCast Production Deployment Script
# This script automates the deployment process for production

set -e

echo "🚀 Starting SarvCast Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    print_error "Please do not run this script as root"
    exit 1
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create environment file if it doesn't exist
if [ ! -f .env.production ]; then
    print_warning "Creating .env.production from template..."
    cp .env.production.example .env.production
    print_warning "Please edit .env.production with your production values before continuing."
    print_warning "Required values:"
    print_warning "  - APP_KEY (generate with: php artisan key:generate)"
    print_warning "  - DB_PASSWORD"
    print_warning "  - REDIS_PASSWORD"
    print_warning "  - SMS_API_KEY"
    print_warning "  - ZARINPAL_MERCHANT_ID"
    print_warning "  - AWS credentials (if using S3)"
    print_warning "  - FIREBASE_SERVER_KEY"
    read -p "Press Enter to continue after updating .env.production..."
fi

# Validate environment file
if [ ! -f .env.production ]; then
    print_error ".env.production file not found!"
    exit 1
fi

# Check for required environment variables
print_status "Validating environment configuration..."

# Source the environment file
source .env.production

# Check required variables
required_vars=(
    "APP_KEY"
    "DB_PASSWORD"
    "REDIS_PASSWORD"
    "SMS_API_KEY"
    "ZARINPAL_MERCHANT_ID"
)

for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ] || [ "${!var}" = "YOUR_${var}_HERE" ] || [ "${!var}" = "YOUR_SECURE_${var}" ]; then
        print_error "Required environment variable $var is not set or still has placeholder value"
        exit 1
    fi
done

print_status "Environment validation passed!"

# Create necessary directories
print_status "Creating necessary directories..."
mkdir -p storage/app/public
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Set permissions
print_status "Setting file permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Build and start services
print_status "Building Docker images..."
docker-compose -f docker-compose.production.yml build --no-cache

print_status "Starting services..."
docker-compose -f docker-compose.production.yml up -d

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Check if services are running
print_status "Checking service status..."
if ! docker-compose -f docker-compose.production.yml ps | grep -q "Up"; then
    print_error "Some services failed to start. Check logs with: docker-compose -f docker-compose.production.yml logs"
    exit 1
fi

# Run database migrations
print_status "Running database migrations..."
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Seed database
print_status "Seeding database..."
docker-compose -f docker-compose.production.yml exec app php artisan db:seed --force

# Generate application key if not set
if [ "$APP_KEY" = "base64:YOUR_APP_KEY_HERE" ]; then
    print_status "Generating application key..."
    docker-compose -f docker-compose.production.yml exec app php artisan key:generate --force
fi

# Clear and optimize caches
print_status "Clearing and optimizing caches..."
docker-compose -f docker-compose.production.yml exec app php artisan config:clear
docker-compose -f docker-compose.production.yml exec app php artisan route:clear
docker-compose -f docker-compose.production.yml exec app php artisan view:clear
docker-compose -f docker-compose.production.yml exec app php artisan cache:clear

# Create storage symlink
print_status "Creating storage symlink..."
docker-compose -f docker-compose.production.yml exec app php artisan storage:link --force

# Optimize application
print_status "Optimizing application..."
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache

# Run performance optimization
print_status "Running performance optimization..."
docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:optimize-performance

# Test application health
print_status "Testing application health..."
sleep 10

# Check if API is responding
if curl -f http://localhost/api/v1/health > /dev/null 2>&1; then
    print_status "API health check passed!"
else
    print_warning "API health check failed. Check logs for issues."
fi

# Create initial backup
print_status "Creating initial backup..."
docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:backup --type=full

# Setup SSL certificates (if using Let's Encrypt)
read -p "Do you want to setup SSL certificates with Let's Encrypt? (y/n): " setup_ssl
if [ "$setup_ssl" = "y" ] || [ "$setup_ssl" = "Y" ]; then
    print_status "Setting up SSL certificates..."

    # Install certbot if not installed
    if ! command -v certbot &> /dev/null; then
        print_status "Installing Certbot..."
        sudo apt update
        sudo apt install certbot python3-certbot-nginx -y
    fi

    # Get SSL certificates
    print_status "Obtaining SSL certificates..."
    sudo certbot --nginx -d api.sarvcast.com -d admin.sarvcast.com --non-interactive --agree-tos --email admin@sarvcast.com

    # Setup auto-renewal
    print_status "Setting up SSL auto-renewal..."
    (crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -
fi

# Setup monitoring
print_status "Setting up monitoring..."
docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:monitor

# Final status check
print_status "Performing final status check..."
docker-compose -f docker-compose.production.yml ps

# Display access information
print_status "Deployment completed successfully!"
echo ""
echo "🌐 Access Information:"
echo "  API: https://api.sarvcast.com"
echo "  Admin Dashboard: https://admin.sarvcast.com"
echo "  Health Check: https://api.sarvcast.com/api/v1/health"
echo "  Monitoring: http://localhost:9090 (if enabled)"
echo ""
echo "📋 Next Steps:"
echo "  1. Update DNS records to point to this server"
echo "  2. Configure your domain names (api.sarvcast.com, admin.sarvcast.com)"
echo "  3. Test all functionality"
echo "  4. Setup monitoring alerts"
echo "  5. Configure backup notifications"
echo ""
echo "🔧 Useful Commands:"
echo "  View logs: docker-compose -f docker-compose.production.yml logs -f"
echo "  Restart services: docker-compose -f docker-compose.production.yml restart"
echo "  Stop services: docker-compose -f docker-compose.production.yml down"
echo "  Create backup: docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:backup --type=full"
echo "  Monitor health: docker-compose -f docker-compose.production.yml exec app php artisan sarvcast:monitor"
echo ""
echo "📚 Documentation:"
echo "  Production Guide: docs/PRODUCTION_DEPLOYMENT_GUIDE.md"
echo "  API Documentation: docs/API_DOCUMENTATION_COMPLETE.md"
echo "  Admin Guide: docs/ADMIN_USER_GUIDE.md"
echo ""

print_status "SarvCast is now running in production! 🎉"
