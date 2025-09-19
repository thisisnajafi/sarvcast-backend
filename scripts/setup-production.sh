#!/bin/bash

# SarvCast Production Setup Script
set -e

PROJECT_NAME="sarvcast"
DOMAIN="sarvcast.com"

log() {
    echo -e "\033[0;34m[$(date +'%Y-%m-%d %H:%M:%S')]\033[0m $1"
}

success() {
    echo -e "\033[0;32m✅ $1\033[0m"
}

error() {
    echo -e "\033[0;31m❌ $1\033[0m"
    exit 1
}

log "Setting up production environment..."

# Install Docker
if ! command -v docker &> /dev/null; then
    log "Installing Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    systemctl enable docker
    systemctl start docker
    success "Docker installed"
fi

# Install Docker Compose
if ! command -v docker-compose &> /dev/null; then
    log "Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    success "Docker Compose installed"
fi

# Create directories
log "Creating directories..."
mkdir -p /var/www/sarvcast
mkdir -p /backups/sarvcast
mkdir -p /var/log/sarvcast
mkdir -p /etc/nginx/ssl

# Set permissions
chmod +x scripts/*.sh

# Generate SSL certificates
log "Generating SSL certificates..."
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/sarvcast.key \
    -out /etc/nginx/ssl/sarvcast.crt \
    -subj "/C=IR/ST=Tehran/L=Tehran/O=SarvCast/OU=IT/CN=$DOMAIN"

success "Production environment setup completed!"
