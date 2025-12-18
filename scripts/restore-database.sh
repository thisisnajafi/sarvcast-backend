#!/bin/bash

# Database Restore Script
# This script restores a database from a backup file
# Usage: ./restore-database.sh <backup_file.sql.gz>

set -e

# Configuration
LOG_FILE="${LOG_FILE:-/tmp/sarvcast-restore.log}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}✅ $1${NC}" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}❌ $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

# Check if backup file is provided
if [ -z "$1" ]; then
    error "Usage: $0 <backup_file.sql.gz>"
fi

BACKUP_FILE="$1"

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    error "Backup file not found: $BACKUP_FILE"
fi

log "Starting database restore process..."
log "Backup file: $BACKUP_FILE"

# Load database credentials from .env file
if [ -f .env ]; then
    log "Loading database credentials from .env file..."
    export $(grep -v '^#' .env | grep -E '^DB_' | xargs)
else
    error ".env file not found. Cannot determine database credentials."
fi

# Validate database credentials
if [ -z "$DB_CONNECTION" ] || [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    error "Database credentials not found in .env file"
fi

# Set default values
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_PASSWORD="${DB_PASSWORD:-}"

log "Database: $DB_DATABASE"
log "Host: $DB_HOST"
log "Port: $DB_PORT"
log "User: $DB_USERNAME"

# Confirm restore
echo ""
echo "⚠️  WARNING: This will overwrite the current database!"
echo "   • Current data will be lost"
echo "   • Make sure you have a current backup"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    log "Restore cancelled by user"
    exit 0
fi

# Create temporary directory for extraction
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

# Extract backup if compressed
if [[ "$BACKUP_FILE" == *.gz ]]; then
    log "Extracting compressed backup..."
    gunzip -c "$BACKUP_FILE" > "$TEMP_DIR/restore.sql"
    RESTORE_FILE="$TEMP_DIR/restore.sql"
else
    RESTORE_FILE="$BACKUP_FILE"
fi

# Verify backup file
log "Verifying backup file..."
if [ ! -s "$RESTORE_FILE" ]; then
    error "Backup file is empty or corrupted"
fi

# Restore database
log "Restoring database..."

if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
    # MySQL/MariaDB restore
    if [ -n "$DB_PASSWORD" ]; then
        mysql \
            -h "$DB_HOST" \
            -P "$DB_PORT" \
            -u "$DB_USERNAME" \
            -p"$DB_PASSWORD" \
            "$DB_DATABASE" < "$RESTORE_FILE" 2>>"$LOG_FILE"
    else
        mysql \
            -h "$DB_HOST" \
            -P "$DB_PORT" \
            -u "$DB_USERNAME" \
            "$DB_DATABASE" < "$RESTORE_FILE" 2>>"$LOG_FILE"
    fi
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    # PostgreSQL restore
    export PGPASSWORD="$DB_PASSWORD"
    pg_restore \
        -h "$DB_HOST" \
        -p "$DB_PORT" \
        -U "$DB_USERNAME" \
        -d "$DB_DATABASE" \
        --no-owner \
        --no-acl \
        "$RESTORE_FILE" 2>>"$LOG_FILE"
else
    error "Unsupported database connection: $DB_CONNECTION"
fi

# Check if restore was successful
if [ $? -eq 0 ]; then
    success "Database restored successfully"
else
    error "Database restore failed"
fi

# Clear Laravel caches
log "Clearing Laravel caches..."
if [ -f "artisan" ]; then
    log "Clearing Laravel caches..."
    php artisan cache:clear 2>/dev/null && success "Cache cleared" || warning "Cache clear failed"
    php artisan config:clear 2>/dev/null && success "Config cache cleared" || warning "Config cache clear failed"
    php artisan view:clear 2>/dev/null && success "View cache cleared" || warning "View cache clear failed"
    php artisan route:clear 2>/dev/null && success "Route cache cleared" || warning "Route cache clear failed"
    success "Laravel cache clearing completed"
fi

# Display summary
echo ""
echo "📋 Restore Summary:"
echo "   • Backup File: $BACKUP_FILE"
echo "   • Database: $DB_DATABASE"
echo "   • Restore Time: $(date)"
echo ""

success "Database restore completed successfully!"

