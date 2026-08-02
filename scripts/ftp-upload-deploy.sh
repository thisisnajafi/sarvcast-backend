#!/usr/bin/env bash
set -euo pipefail

# Upload deploy bundle via FTPS. Uses absolute local paths so lftp never mis-resolves lcd.
FTP_SERVER="${FTP_SERVER:?}"
FTP_USERNAME="${FTP_USERNAME:?}"
FTP_PASSWORD="${FTP_PASSWORD:?}"
SERVER_DIR="${FTP_SERVER_DIR:-/}"
UPLOAD_VENDOR="${UPLOAD_VENDOR:-false}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
UPLOAD_DIR="${ROOT}/deploy-upload"
PUBLIC_DIR="${UPLOAD_DIR}/public"

require_file() {
  if [[ ! -f "$1" ]]; then
    echo "ftp-upload-deploy: ERROR — missing required file: $1"
    exit 1
  fi
}

require_file "${UPLOAD_DIR}/deploy.zip"
require_file "${PUBLIC_DIR}/extract-deploy.php"
require_file "${PUBLIC_DIR}/_deploy_helper.php"

echo "ftp-upload-deploy: bundle contents"
ls -lh "${UPLOAD_DIR}/deploy.zip"
if [[ -f "${UPLOAD_DIR}/vendor.zip" ]]; then
  ls -lh "${UPLOAD_DIR}/vendor.zip"
else
  echo "ftp-upload-deploy: vendor.zip not in bundle (server keeps existing vendor/)"
fi
ls -lh "${PUBLIC_DIR}/extract-deploy.php" "${PUBLIC_DIR}/_deploy_helper.php"

build_lftp_script() {
  local script=""
  script+="set ftp:passive-mode true;"
  script+=" set ftp:ssl-allow true;"
  script+=" set ftp:ssl-force true;"
  script+=" set ssl:verify-certificate no;"
  script+=" set ssl:check-hostname no;"
  script+=" set net:timeout 120;"
  script+=" set net:max-retries 5;"
  script+=" set net:reconnect-interval-base 10;"
  script+=" set cmd:fail-exit yes;"
  script+=" cd ${SERVER_DIR};"
  script+=" put -O . \"${UPLOAD_DIR}/deploy.zip\";"

  if [[ "${UPLOAD_VENDOR}" == "true" ]]; then
    require_file "${UPLOAD_DIR}/vendor.zip"
    script+=" put -O . \"${UPLOAD_DIR}/vendor.zip\";"
  fi

  if [[ -f "${UPLOAD_DIR}/htaccess.deploy" ]]; then
    script+=" put -O . \"${UPLOAD_DIR}/htaccess.deploy\";"
  fi

  script+=" cd public;"
  script+=" put -O . \"${PUBLIC_DIR}/extract-deploy.php\";"
  script+=" put -O . \"${PUBLIC_DIR}/_deploy_helper.php\";"
  if [[ -f "${PUBLIC_DIR}/htaccess.deploy" ]]; then
    script+=" put -O . \"${PUBLIC_DIR}/htaccess.deploy\";"
  fi
  script+=" bye;"
  printf '%s' "$script"
}

echo "Connecting to ${FTP_SERVER} as ${FTP_USERNAME} (FTPS)"
echo "Upload vendor.zip: ${UPLOAD_VENDOR}"

for attempt in 1 2 3; do
  echo "FTP deploy attempt ${attempt}/3"
  if lftp -u "${FTP_USERNAME},${FTP_PASSWORD}" "${FTP_SERVER}" -e "$(build_lftp_script)"; then
    echo "FTP upload completed on attempt ${attempt}"
    exit 0
  fi

  if [[ "${attempt}" -eq 3 ]]; then
    echo "::error::FTP upload failed after 3 attempts"
    exit 1
  fi

  echo "FTP upload failed, retrying in 30s..."
  sleep 30
done
