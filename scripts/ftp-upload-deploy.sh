#!/usr/bin/env bash
set -euo pipefail

# Upload deploy bundle via FTPS.
# Strategy: small helper PHP files first, then zips with resume (lftp put -c),
# curl FTPS fallback per file. Avoids one giant brittle session.
FTP_SERVER="${FTP_SERVER:?}"
FTP_USERNAME="${FTP_USERNAME:?}"
FTP_PASSWORD="${FTP_PASSWORD:?}"
SERVER_DIR="${FTP_SERVER_DIR:-/}"
UPLOAD_VENDOR="${UPLOAD_VENDOR:-false}"
PER_FILE_ATTEMPTS="${FTP_PER_FILE_ATTEMPTS:-4}"
OPEN_URL="${FTP_OPEN_URL:-ftps://${FTP_SERVER}}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
UPLOAD_DIR="${ROOT}/deploy-upload"
PUBLIC_DIR="${UPLOAD_DIR}/public"

require_file() {
  if [[ ! -f "$1" ]]; then
    echo "ftp-upload-deploy: ERROR — missing required file: $1"
    exit 1
  fi
}

human_size() {
  local bytes
  bytes=$(wc -c < "$1" | tr -d ' ')
  if command -v numfmt >/dev/null 2>&1; then
    numfmt --to=iec --suffix=B "$bytes"
  else
    echo "${bytes}B"
  fi
}

require_file "${UPLOAD_DIR}/deploy.zip"
require_file "${PUBLIC_DIR}/extract-deploy.php"
require_file "${PUBLIC_DIR}/_deploy_helper.php"

echo "ftp-upload-deploy: bundle contents"
echo "  deploy.zip=$(human_size "${UPLOAD_DIR}/deploy.zip")"
if [[ -f "${UPLOAD_DIR}/vendor.zip" ]]; then
  echo "  vendor.zip=$(human_size "${UPLOAD_DIR}/vendor.zip")"
else
  echo "  vendor.zip not in bundle (server keeps existing vendor/)"
fi
echo "  extract-deploy.php=$(human_size "${PUBLIC_DIR}/extract-deploy.php")"
echo "  _deploy_helper.php=$(human_size "${PUBLIC_DIR}/_deploy_helper.php")"

# Compatible with Ubuntu Actions lftp (no net:idle-timeout).
lftp_settings() {
  cat <<'EOF'
set ftp:passive-mode true;
set ftp:ssl-allow true;
set ftp:ssl-force true;
set ftp:ssl-protect-data true;
set ftp:prefer-epsv false;
set ssl:verify-certificate no;
set ssl:check-hostname no;
set net:timeout 300;
set net:max-retries 5;
set net:reconnect-interval-base 5;
set net:reconnect-interval-multiplier 1;
set xfer:clobber on;
set cmd:fail-exit yes;
EOF
}

upload_with_lftp() {
  local local_file="$1"
  local remote_dir="$2"
  local remote_name="$3"

  lftp -u "${FTP_USERNAME},${FTP_PASSWORD}" "${OPEN_URL}" -e "$(
    lftp_settings
    echo "cd ${SERVER_DIR};"
    if [[ "${remote_dir}" != "." ]]; then
      echo "cd ${remote_dir};"
    fi
    # -c resumes partial uploads after dropped connections.
    echo "put -c \"${local_file}\" -o \"${remote_name}\";"
    echo "bye;"
  )"
}

upload_with_curl() {
  local local_file="$1"
  local remote_dir="$2"
  local remote_name="$3"
  local remote_path

  if [[ "${remote_dir}" == "." ]]; then
    remote_path="${remote_name}"
  else
    remote_path="${remote_dir%/}/${remote_name}"
  fi

  curl --silent --show-error --fail \
    --insecure \
    --ssl-reqd \
    --ftp-pasv \
    --continue-at - \
    --connect-timeout 60 \
    --max-time 2400 \
    --retry 3 \
    --retry-delay 8 \
    --retry-all-errors \
    --user "${FTP_USERNAME}:${FTP_PASSWORD}" \
    --upload-file "${local_file}" \
    "ftp://${FTP_SERVER}/${remote_path}"
}

upload_one() {
  local local_file="$1"
  local remote_dir="$2"
  local remote_name="$3"
  local attempt
  local sleep_s

  echo "Uploading ${remote_name} ($(human_size "${local_file}")) → ${remote_dir%/}/${remote_name}"

  for attempt in $(seq 1 "${PER_FILE_ATTEMPTS}"); do
    echo "  attempt ${attempt}/${PER_FILE_ATTEMPTS} (lftp resume)"
    if upload_with_lftp "${local_file}" "${remote_dir}" "${remote_name}"; then
      echo "  OK: ${remote_name} via lftp"
      return 0
    fi

    echo "  lftp failed; trying curl FTPS resume fallback..."
    if upload_with_curl "${local_file}" "${remote_dir}" "${remote_name}"; then
      echo "  OK: ${remote_name} via curl"
      return 0
    fi

    if [[ "${attempt}" -eq "${PER_FILE_ATTEMPTS}" ]]; then
      echo "::error::FTP upload failed for ${remote_name} after ${PER_FILE_ATTEMPTS} attempts"
      return 1
    fi

    sleep_s=$(( attempt * 15 ))
    echo "  retrying ${remote_name} in ${sleep_s}s..."
    sleep "${sleep_s}"
  done

  return 1
}

echo "Connecting to ${OPEN_URL} as ${FTP_USERNAME}"
echo "Upload vendor.zip: ${UPLOAD_VENDOR}"

# Helpers first so extract works even if a later zip upload is flaky.
upload_one "${PUBLIC_DIR}/extract-deploy.php" "public" "extract-deploy.php"
upload_one "${PUBLIC_DIR}/_deploy_helper.php" "public" "_deploy_helper.php"

if [[ -f "${PUBLIC_DIR}/htaccess.deploy" ]]; then
  upload_one "${PUBLIC_DIR}/htaccess.deploy" "public" "htaccess.deploy"
fi

upload_one "${UPLOAD_DIR}/deploy.zip" "." "deploy.zip"

if [[ -f "${UPLOAD_DIR}/htaccess.deploy" ]]; then
  upload_one "${UPLOAD_DIR}/htaccess.deploy" "." "htaccess.deploy"
fi

if [[ "${UPLOAD_VENDOR}" == "true" ]]; then
  require_file "${UPLOAD_DIR}/vendor.zip"
  upload_one "${UPLOAD_DIR}/vendor.zip" "." "vendor.zip"
fi

echo "FTP upload completed"
