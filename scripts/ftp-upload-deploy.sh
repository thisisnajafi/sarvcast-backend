#!/usr/bin/env bash
set -euo pipefail

# Upload deploy bundle via plain FTP (no TLS).
# Prefer curl (reliable on this host). lftp is secondary with correct lcd/put paths.
FTP_SERVER="${FTP_SERVER:?}"
FTP_USERNAME="${FTP_USERNAME:?}"
FTP_PASSWORD="${FTP_PASSWORD:?}"
SERVER_DIR="${FTP_SERVER_DIR:-/}"
UPLOAD_VENDOR="${UPLOAD_VENDOR:-false}"
PER_FILE_ATTEMPTS="${FTP_PER_FILE_ATTEMPTS:-4}"
OPEN_URL="${FTP_OPEN_URL:-ftp://${FTP_SERVER}}"

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

remote_path_for() {
  local remote_dir="$1"
  local remote_name="$2"
  local base="${SERVER_DIR%/}"
  [[ -z "${base}" || "${base}" == "/" ]] && base=""

  if [[ "${remote_dir}" == "." ]]; then
    echo "${base}/${remote_name}"
  else
    echo "${base}/${remote_dir%/}/${remote_name}"
  fi | sed 's#//*#/#g; s#^/##'
}

# Delete remote file if present (clears corrupt partial uploads that cause FTP 451).
delete_remote() {
  local remote_dir="$1"
  local remote_name="$2"
  local remote_path
  remote_path="$(remote_path_for "${remote_dir}" "${remote_name}")"

  curl --silent --show-error \
    --ftp-pasv \
    --connect-timeout 30 \
    --max-time 60 \
    --user "${FTP_USERNAME}:${FTP_PASSWORD}" \
    -Q "DELE ${remote_path}" \
    "ftp://${FTP_SERVER}/" >/dev/null 2>&1 || true

  if command -v lftp >/dev/null 2>&1; then
    lftp -u "${FTP_USERNAME},${FTP_PASSWORD}" "${OPEN_URL}" <<EOF >/dev/null 2>&1 || true
set ftp:passive-mode true;
set ftp:ssl-allow false;
set ftp:ssl-force false;
set cmd:fail-exit no;
cd ${SERVER_DIR};
$( [[ "${remote_dir}" != "." ]] && echo "cd ${remote_dir};" )
rm -f ${remote_name};
bye
EOF
  fi
}

upload_with_curl() {
  local local_file="$1"
  local remote_dir="$2"
  local remote_name="$3"
  local remote_path
  remote_path="$(remote_path_for "${remote_dir}" "${remote_name}")"

  # Fresh full upload (no --continue-at). Partial resumes were causing 451 on this host.
  curl --silent --show-error --fail \
    --ftp-pasv \
    --connect-timeout 60 \
    --max-time 2400 \
    --retry 2 \
    --retry-delay 5 \
    --retry-all-errors \
    --user "${FTP_USERNAME}:${FTP_PASSWORD}" \
    --upload-file "${local_file}" \
    "ftp://${FTP_SERVER}/${remote_path}"
}

upload_with_lftp() {
  local local_file="$1"
  local remote_dir="$2"
  local remote_name="$3"
  local local_dir local_base

  if ! command -v lftp >/dev/null 2>&1; then
    return 1
  fi

  local_dir="$(dirname "${local_file}")"
  local_base="$(basename "${local_file}")"

  # Heredoc + lcd avoids the broken quoted absolute-path put from -e "$(...)".
  lftp -u "${FTP_USERNAME},${FTP_PASSWORD}" "${OPEN_URL}" <<EOF
set ftp:passive-mode true;
set ftp:ssl-allow false;
set ftp:ssl-force false;
set ftp:prefer-epsv false;
set net:timeout 300;
set net:max-retries 3;
set net:reconnect-interval-base 5;
set xfer:clobber on;
set cmd:fail-exit yes;
cd ${SERVER_DIR};
$( [[ "${remote_dir}" != "." ]] && echo "cd ${remote_dir};" )
lcd ${local_dir};
put -c ${local_base} -o ${remote_name};
bye
EOF
}

upload_one() {
  local local_file="$1"
  local remote_dir="$2"
  local remote_name="$3"
  local attempt
  local sleep_s

  echo "Uploading ${remote_name} ($(human_size "${local_file}")) → $(remote_path_for "${remote_dir}" "${remote_name}")"

  for attempt in $(seq 1 "${PER_FILE_ATTEMPTS}"); do
    echo "  attempt ${attempt}/${PER_FILE_ATTEMPTS}"

    # Only delete after a failed attempt. Clearing vendor.zip before every try
    # forced a full re-upload (no resume) and could burn 40+ minutes on retries.
    if [[ "${attempt}" -gt 1 ]]; then
      echo "  clearing remote ${remote_name} before retry..."
      delete_remote "${remote_dir}" "${remote_name}"
    fi

    echo "  curl FTP..."
    if upload_with_curl "${local_file}" "${remote_dir}" "${remote_name}"; then
      echo "  OK: ${remote_name} via curl"
      return 0
    fi

    echo "  curl failed; trying lftp..."
    if upload_with_lftp "${local_file}" "${remote_dir}" "${remote_name}"; then
      echo "  OK: ${remote_name} via lftp"
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

echo "Connecting to ${OPEN_URL} as ${FTP_USERNAME} (plain FTP)"
echo "Upload vendor.zip: ${UPLOAD_VENDOR}"

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
else
  echo "Skipping vendor.zip upload; removing leftover remote vendor.zip if present"
  delete_remote "." "vendor.zip"
fi

echo "FTP upload completed"
