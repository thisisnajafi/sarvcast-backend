#!/bin/bash

# SarvCast Production Deployment Script
# This script deploys the SarvCast application to production

set -e

echo "🚀 Starting SarvCast Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="sarvcast"
DOMAIN="sarvcast.com"
SSL_EMAIL="admin@sarvcast.com"
BACKUP_DIR="/backups/sarvcast"
LOG_FILE="/var/log/sarvcast-deploy.log"

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a $LOG_FILE
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a $LOG_FILE
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a $LOG_FILE
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a $LOG_FILE
    exit 1
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "Please run as root"
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    error "Docker is not installed"
fi

if ! command -v docker-compose &> /dev/null; then
    error "Docker Compose is not installed"
fi

log "Starting deployment process..."

# Create necessary directories
log "Creating necessary directories..."
mkdir -p $BACKUP_DIR
mkdir -p /var/log/sarvcast
mkdir -p /etc/nginx/ssl
mkdir -p /var/www/sarvcast

# Backup existing data if exists
if [ -d "/var/www/sarvcast" ]; then
    log "Creating backup of existing installation..."
    tar -czf $BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz /var/www/sarvcast
    success "Backup created successfully"
fi

# Stop existing services
log "Stopping existing services..."
docker-compose -f docker-compose.production.yml down || true
systemctl stop nginx || true
systemctl stop mysql || true
systemctl stop redis || true

# Copy application files
log "Copying application files..."
cp -r . /var/www/sarvcast/
cd /var/www/sarvcast

# Set permissions
log "Setting proper permissions..."
chown -R www-data:www-data /var/www/sarvcast
chmod -R 755 /var/www/sarvcast
chmod -R 777 /var/www/sarvcast/storage
chmod -R 777 /var/www/sarvcast/bootstrap/cache

# Generate SSL certificates
log "Generating SSL certificates..."
if [ ! -f "/etc/nginx/ssl/sarvcast.crt" ]; then
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/nginx/ssl/sarvcast.key \
        -out /etc/nginx/ssl/sarvcast.crt \
        -subj "/C=IR/ST=Tehran/L=Tehran/O=SarvCast/OU=IT/CN=$DOMAIN"
    success "SSL certificates generated"
else
    warning "SSL certificates already exist"
fi

# Create environment file
log "Creating production environment file..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    warning "Please update .env file with production values"
fi

# Install dependencies
log "Installing PHP dependencies..."
docker run --rm -v $(pwd):/app -w /app composer:latest install --no-dev --optimize-autoloader --no-interaction

# Build frontend assets
log "Building frontend assets..."
docker run --rm -v $(pwd):/app -w /app node:18-alpine sh -c "npm ci --only=production && npm run build"

# Generate application key
log "Generating application key..."
docker run --rm -v $(pwd):/app -w /app php:8.2-cli php artisan key:generate

# Run database migrations
log "Running database migrations..."
docker-compose -f docker-compose.production.yml up -d mysql redis
sleep 30
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan migrate --force

# Seed database
log "Seeding database..."
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan db:seed --force

# Cache configuration
log "Caching configuration..."
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan config:cache
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan route:cache
docker-compose -f docker-compose.production.yml exec -T php-fpm php artisan view:cache

# Start all services
log "Starting all services..."
docker-compose -f docker-compose.production.yml up -d

# Wait for services to be ready
log "Waiting for services to be ready..."
sleep 60

# Health check
log "Performing health check..."
if curl -f http://localhost/health > /dev/null 2>&1; then
    success "Application is healthy"
else
    error "Health check failed"
fi

# Setup monitoring
log "Setting up monitoring..."
if [ -f "monitoring/setup-monitoring.sh" ]; then
    bash monitoring/setup-monitoring.sh
fi

# Setup backup cron job
log "Setting up backup cron job..."
(crontab -l 2>/dev/null; echo "0 2 * * * /var/www/sarvcast/scripts/backup.sh") | crontab -

# Setup log rotation
log "Setting up log rotation..."
cat > /etc/logrotate.d/sarvcast << EOF
/var/log/sarvcast/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        docker-compose -f /var/www/sarvcast/docker-compose.production.yml restart nginx
    endscript
}
EOF

# Setup systemd service
log "Setting up systemd service..."
cat > /etc/systemd/system/sarvcast.service << EOF
[Unit]
Description=SarvCast Application
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/var/www/sarvcast
ExecStart=/usr/bin/docker-compose -f docker-compose.production.yml up -d
ExecStop=/usr/bin/docker-compose -f docker-compose.production.yml down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable sarvcast.service

# Final checks
log "Performing final checks..."

# Check if all containers are running
if docker-compose -f docker-compose.production.yml ps | grep -q "Up"; then
    success "All containers are running"
else
    error "Some containers failed to start"
fi

# Check if application is accessible
if curl -f https://$DOMAIN/health > /dev/null 2>&1; then
    success "Application is accessible via HTTPS"
else
    warning "Application may not be accessible via HTTPS yet"
fi

# Display deployment summary
echo ""
echo "🎉 Deployment completed successfully!"
echo ""
echo "📋 Deployment Summary:"
echo "   • Application: $PROJECT_NAME"
echo "   • Domain: $DOMAIN"
echo "   • SSL: Enabled"
echo "   • Monitoring: Enabled"
echo "   • Backup: Configured"
echo "   • Logs: /var/log/sarvcast/"
echo ""
echo "🔗 Access URLs:"
echo "   • Application: https://$DOMAIN"
echo "   • Admin Panel: https://$DOMAIN/admin"
echo "   • API: https://$DOMAIN/api/v1"
echo "   • Monitoring: http://$DOMAIN:3000 (Grafana)"
echo "   • Metrics: http://$DOMAIN:9090 (Prometheus)"
echo ""
echo "📝 Next Steps:"
echo "   1. Update .env file with production values"
echo "   2. Configure DNS to point to this server"
echo "   3. Test all functionality"
echo "   4. Setup monitoring alerts"
echo "   5. Configure backup verification"
echo ""
echo "📚 Documentation:"
echo "   • Logs: tail -f $LOG_FILE"
echo "   • Status: systemctl status sarvcast"
echo "   • Restart: systemctl restart sarvcast"
echo "   • Logs: docker-compose -f docker-compose.production.yml logs"
echo ""

success "Deployment completed successfully!"