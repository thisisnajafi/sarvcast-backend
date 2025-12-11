#!/bin/bash

# Update database schema safely while keeping existing data
# Usage: BACKUP_DIR=/path/to/backups LOG_FILE=/tmp/sarvcast-db-update.log ./scripts/update-database.sh

set -e

# Defaults
LOG_FILE="${LOG_FILE:-/tmp/sarvcast-db-update.log}"
BACKUP_DIR="${BACKUP_DIR:-storage/backups}"

# Normalize BACKUP_DIR to absolute path
if [[ "$BACKUP_DIR" != /* ]]; then
  BACKUP_DIR="$(pwd)/$BACKUP_DIR"
fi

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

log "Starting database update..."
log "Backup dir: $BACKUP_DIR"
log "Log file: $LOG_FILE"

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

# Run safe migrate with provided backup dir and log file
if [ -f "scripts/safe-migrate.sh" ]; then
  chmod +x scripts/safe-migrate.sh
  log "Running safe migrations..."
  BACKUP_DIR="$BACKUP_DIR" LOG_FILE="$LOG_FILE" ./scripts/safe-migrate.sh --rollback-on-failure
  success "Database update completed"
else
  error "scripts/safe-migrate.sh not found"
fi

