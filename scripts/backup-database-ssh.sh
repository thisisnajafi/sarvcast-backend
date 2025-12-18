#!/bin/bash

# Database Backup Script for SSH-based Deployment
# This script creates a database backup before migrations
# Usage: ./backup-database-ssh.sh [backup_name]

set -e

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/backups/sarvcast}"
RETENTION_DAYS=30
LOG_FILE="${LOG_FILE:-/tmp/sarvcast-backup.log}"

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

# Get backup name from argument or generate timestamp
if [ -n "$1" ]; then
    BACKUP_NAME="$1"
else
    BACKUP_NAME="backup_$(date +%Y%m%d_%H%M%S)"
fi

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

log "Starting database backup process..."
log "Backup name: $BACKUP_NAME"

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

# Generate backup file path
BACKUP_FILE="$BACKUP_DIR/${BACKUP_NAME}.sql"
BACKUP_FILE_COMPRESSED="$BACKUP_DIR/${BACKUP_NAME}.sql.gz"

# Create database backup
log "Creating database backup..."

if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
    # MySQL/MariaDB backup
    if [ -n "$DB_PASSWORD" ]; then
        mysqldump \
            -h "$DB_HOST" \
            -P "$DB_PORT" \
            -u "$DB_USERNAME" \
            -p"$DB_PASSWORD" \
            --single-transaction \
            --routines \
            --triggers \
            --events \
            --quick \
            --lock-tables=false \
            "$DB_DATABASE" > "$BACKUP_FILE" 2>>"$LOG_FILE"
    else
        mysqldump \
            -h "$DB_HOST" \
            -P "$DB_PORT" \
            -u "$DB_USERNAME" \
            --single-transaction \
            --routines \
            --triggers \
            --events \
            --quick \
            --lock-tables=false \
            "$DB_DATABASE" > "$BACKUP_FILE" 2>>"$LOG_FILE"
    fi
elif [ "$DB_CONNECTION" = "pgsql" ]; then
    # PostgreSQL backup
    export PGPASSWORD="$DB_PASSWORD"
    pg_dump \
        -h "$DB_HOST" \
        -p "$DB_PORT" \
        -U "$DB_USERNAME" \
        -d "$DB_DATABASE" \
        --no-owner \
        --no-acl \
        -F c \
        -f "$BACKUP_FILE" 2>>"$LOG_FILE"
else
    error "Unsupported database connection: $DB_CONNECTION"
fi

# Check if backup was successful
if [ $? -eq 0 ] && [ -f "$BACKUP_FILE" ]; then
    success "Database backup created successfully"
else
    error "Database backup failed"
fi

# Compress backup
log "Compressing backup..."
gzip -f "$BACKUP_FILE" 2>>"$LOG_FILE"

if [ -f "$BACKUP_FILE_COMPRESSED" ]; then
    success "Backup compressed successfully"
else
    error "Backup compression failed"
fi

# Get backup size
BACKUP_SIZE=$(du -h "$BACKUP_FILE_COMPRESSED" | cut -f1)
log "Backup size: $BACKUP_SIZE"

# Create backup metadata
METADATA_FILE="$BACKUP_DIR/${BACKUP_NAME}.json"
cat > "$METADATA_FILE" << EOF
{
    "backup_name": "$BACKUP_NAME",
    "timestamp": "$(date -Iseconds)",
    "database": "$DB_DATABASE",
    "host": "$DB_HOST",
    "size": "$BACKUP_SIZE",
    "file": "${BACKUP_NAME}.sql.gz",
    "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo 'unknown')",
    "git_branch": "$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'unknown')"
}
EOF

success "Backup metadata created"

# Cleanup old backups
log "Cleaning up old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "*.sql.gz" -type f -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
find "$BACKUP_DIR" -name "*.json" -type f -mtime +$RETENTION_DAYS -delete 2>/dev/null || true

BACKUP_COUNT=$(find "$BACKUP_DIR" -name "*.sql.gz" -type f | wc -l)
log "Remaining backups: $BACKUP_COUNT"

# Display backup summary
echo ""
echo "📋 Backup Summary:"
echo "   • Backup Name: $BACKUP_NAME"
echo "   • Backup Size: $BACKUP_SIZE"
echo "   • Backup Location: $BACKUP_FILE_COMPRESSED"
echo "   • Retention: $RETENTION_DAYS days"
echo "   • Remaining Backups: $BACKUP_COUNT"
echo ""

success "Database backup completed successfully!"
echo "$BACKUP_FILE_COMPRESSED"

# Clear Laravel caches after successful backup
log "Clearing Laravel caches..."
if command -v php >/dev/null 2>&1 && [ -f "artisan" ]; then
    log "Running Laravel cache clearing commands..."
    php artisan cache:clear 2>>"$LOG_FILE" && success "Cache cleared" || warning "Cache clear failed"
    php artisan config:clear 2>>"$LOG_FILE" && success "Config cache cleared" || warning "Config cache clear failed"
    php artisan view:clear 2>>"$LOG_FILE" && success "View cache cleared" || warning "View cache clear failed"
    php artisan route:clear 2>>"$LOG_FILE" && success "Route cache cleared" || warning "Route cache clear failed"
    success "Laravel cache clearing completed"
else
    warning "PHP or artisan not found, skipping cache clearing"
fi

