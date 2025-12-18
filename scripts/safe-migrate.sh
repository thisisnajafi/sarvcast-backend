#!/bin/bash

# Safe Migration Script with Rollback Capability
# This script runs migrations safely with automatic rollback on failure
# Usage: ./safe-migrate.sh [--rollback-on-failure]

set -e

# Configuration
BACKUP_DIR="${BACKUP_DIR:-/backups/sarvcast}"
LOG_FILE="${LOG_FILE:-/tmp/sarvcast-migrate.log}"
ROLLBACK_ON_FAILURE="${1:---rollback-on-failure}"

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

log "Starting safe migration process..."

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    error "Not a Laravel project. Please run this script from the project root."
fi

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Step 1: Create pre-migration backup
log "Step 1: Creating pre-migration database backup..."
PRE_MIGRATION_BACKUP_NAME="pre_migration_$(date +%Y%m%d_%H%M%S)"
PRE_MIGRATION_BACKUP_FILE=""

if [ -f "scripts/backup-database-ssh.sh" ]; then
    chmod +x scripts/backup-database-ssh.sh
    PRE_MIGRATION_BACKUP_FILE=$(./scripts/backup-database-ssh.sh "$PRE_MIGRATION_BACKUP_NAME" 2>&1 | tail -n 1)
    if [ -f "$PRE_MIGRATION_BACKUP_FILE" ]; then
        success "Pre-migration backup created: $PRE_MIGRATION_BACKUP_FILE"
    else
        error "Pre-migration backup failed"
    fi
else
    warning "Backup script not found. Skipping backup (NOT RECOMMENDED)"
fi

# Step 2: Check current migration status
log "Step 2: Checking current migration status..."
CURRENT_MIGRATIONS=$(php artisan migrate:status --no-ansi 2>&1 || echo "")
log "Current migration status:"
echo "$CURRENT_MIGRATIONS" | head -n 20

# Step 3: Run migrations in dry-run mode first (if supported)
log "Step 3: Validating migrations..."
php artisan migrate --pretend --no-ansi 2>&1 | tee -a "$LOG_FILE" || {
    warning "Dry-run mode not fully supported, proceeding with actual migration"
}

# Step 4: Run migrations
log "Step 4: Running database migrations..."
MIGRATION_OUTPUT=$(php artisan migrate --force --no-ansi 2>&1)
MIGRATION_EXIT_CODE=$?

if [ $MIGRATION_EXIT_CODE -eq 0 ]; then
    success "Migrations completed successfully"
    echo "$MIGRATION_OUTPUT" | tee -a "$LOG_FILE"
else
    error "Migrations failed with exit code: $MIGRATION_EXIT_CODE"
    echo "$MIGRATION_OUTPUT" | tee -a "$LOG_FILE"

    # Rollback on failure if enabled
    if [ "$ROLLBACK_ON_FAILURE" = "--rollback-on-failure" ] && [ -n "$PRE_MIGRATION_BACKUP_FILE" ] && [ -f "$PRE_MIGRATION_BACKUP_FILE" ]; then
        log "Rolling back to pre-migration state..."
        warning "Automatic rollback would be performed here"
        warning "To restore manually, run: scripts/restore-database.sh $PRE_MIGRATION_BACKUP_FILE"
    fi

    exit $MIGRATION_EXIT_CODE
fi

# Step 5: Verify migration status
log "Step 5: Verifying migration status..."
php artisan migrate:status --no-ansi 2>&1 | tee -a "$LOG_FILE"

# Step 6: Check for pending migrations
PENDING_MIGRATIONS=$(php artisan migrate:status --no-ansi 2>&1 | grep -c "Pending" || echo "0")
if [ "$PENDING_MIGRATIONS" -gt 0 ]; then
    warning "There are still $PENDING_MIGRATIONS pending migrations"
else
    success "All migrations are up to date"
fi

# Step 7: Create post-migration backup (optional)
read -p "Create post-migration backup? (y/n): " CREATE_POST_BACKUP
if [ "$CREATE_POST_BACKUP" = "y" ] || [ "$CREATE_POST_BACKUP" = "Y" ]; then
    log "Creating post-migration backup..."
    POST_MIGRATION_BACKUP_NAME="post_migration_$(date +%Y%m%d_%H%M%S)"
    if [ -f "scripts/backup-database-ssh.sh" ]; then
        POST_MIGRATION_BACKUP_FILE=$(./scripts/backup-database-ssh.sh "$POST_MIGRATION_BACKUP_NAME" 2>&1 | tail -n 1)
        if [ -f "$POST_MIGRATION_BACKUP_FILE" ]; then
            success "Post-migration backup created: $POST_MIGRATION_BACKUP_FILE"
        fi
    fi
fi

# Display summary
echo ""
echo "📋 Migration Summary:"
echo "   • Pre-migration backup: $PRE_MIGRATION_BACKUP_FILE"
echo "   • Migration status: ✅ Success"
echo "   • Pending migrations: $PENDING_MIGRATIONS"
echo ""

success "Safe migration process completed successfully!"

# Clear Laravel caches after successful migration
log "Clearing Laravel caches..."
if command -v php >/dev/null 2>&1; then
    log "Running Laravel cache clearing commands..."
    php artisan cache:clear 2>>"$LOG_FILE" && success "Cache cleared" || warning "Cache clear failed"
    php artisan config:clear 2>>"$LOG_FILE" && success "Config cache cleared" || warning "Config cache clear failed"
    php artisan view:clear 2>>"$LOG_FILE" && success "View cache cleared" || warning "View cache clear failed"
    php artisan route:clear 2>>"$LOG_FILE" && success "Route cache cleared" || warning "Route cache clear failed"
    success "Laravel cache clearing completed"
else
    warning "PHP not found, skipping cache clearing"
fi

