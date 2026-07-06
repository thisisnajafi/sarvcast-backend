#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/deploy-upload/.env"
TEMPLATE="$ROOT/.github/production.env.template"

escape_sed_replacement() {
  printf '%s' "$1" | sed -e 's/[\/&]/\\&/g'
}

strip_wrapping_quotes() {
  local value="$1"
  value="${value#\'}"
  value="${value%\'}"
  value="${value#\"}"
  value="${value%\"}"
  printf '%s' "$value"
}

# Values with # must be quoted in .env or everything after # is treated as a comment.
quote_env_value() {
  local v="$1"
  v="${v//\\/\\\\}"
  v="${v//\"/\\\"}"
  printf '"%s"' "$v"
}

needs_env_quoting() {
  local v="$1"
  [[ "$v" == *"#"* ]] || [[ "$v" == *" "* ]] || [[ "$v" == *$'\t'* ]] || [[ "$v" == *'"'* ]]
}

set_env_line() {
  local key="$1"
  local raw="$2"
  local formatted="$raw"

  if needs_env_quoting "$raw"; then
    formatted="$(quote_env_value "$raw")"
  fi

  local escaped
  escaped="$(escape_sed_replacement "$formatted")"
  sed -i "s|^${key}=.*|${key}=${escaped}|" "$OUT" || true
}

SENSITIVE_ENV_KEYS=(
  DB_PASSWORD
  MELIPAYAMAK_PASSWORD
)

apply_ci_secret_overrides() {
  if [ ! -f "$OUT" ]; then
    return 1
  fi

  if [ -n "${PRODUCTION_DB_DATABASE:-}" ]; then
    set_env_line DB_DATABASE "$(strip_wrapping_quotes "$PRODUCTION_DB_DATABASE")"
  fi

  if [ -n "${PRODUCTION_DB_USERNAME:-}" ]; then
    set_env_line DB_USERNAME "$(strip_wrapping_quotes "$PRODUCTION_DB_USERNAME")"
  fi

  local effective_db_pass="${PRODUCTION_DB_PASSWORD:-${FTP_PASSWORD:-}}"
  if [ -n "$effective_db_pass" ]; then
    set_env_line DB_PASSWORD "$(strip_wrapping_quotes "$effective_db_pass")"
    if [ -n "${PRODUCTION_DB_PASSWORD:-}" ]; then
      echo "prepare-production-env: applied PRODUCTION_DB_PASSWORD from CI secret"
    else
      echo "prepare-production-env: applied DB_PASSWORD from FTP_PASSWORD (CI env)"
    fi
  fi

  local effective_sms_pass="${PRODUCTION_MELIPAYAMAK_PASSWORD:-${FTP_PASSWORD:-}}"
  if [ -n "$effective_sms_pass" ]; then
    set_env_line MELIPAYAMAK_PASSWORD "$(strip_wrapping_quotes "$effective_sms_pass")"
    if [ -n "${PRODUCTION_MELIPAYAMAK_PASSWORD:-}" ]; then
      echo "prepare-production-env: applied PRODUCTION_MELIPAYAMAK_PASSWORD from CI secret"
    else
      echo "prepare-production-env: applied MELIPAYAMAK_PASSWORD from FTP_PASSWORD (CI env)"
    fi
  fi
}

ensure_sensitive_env_values_quoted() {
  if [ ! -f "$OUT" ]; then
    return 1
  fi

  local key line raw
  for key in "${SENSITIVE_ENV_KEYS[@]}"; do
    line="$(grep -m1 "^${key}=" "$OUT" || true)"
    [ -z "$line" ] && continue

    raw="${line#${key}=}"
    raw="$(strip_wrapping_quotes "$raw")"

    if needs_env_quoting "$raw"; then
      set_env_line "$key" "$raw"
      echo "prepare-production-env: quoted ${key} for safe .env parsing (# and special chars)"
    fi
  done
}

normalize_production_env() {
  if [ ! -f "$OUT" ]; then
    return 1
  fi

  # Do NOT strip quotes from DB_PASSWORD — passwords with # break when unquoted in .env.

  sed -i 's|^APP_URL=.*|APP_URL=https://my.manjiapp.ir|' "$OUT" || true
  sed -i 's|^APP_ENV=.*|APP_ENV=production|' "$OUT" || true
  sed -i 's|^APP_DEBUG=.*|APP_DEBUG=false|' "$OUT" || true
  sed -i 's|^FIREBASE_SERVICE_ACCOUNT_PATH=.*|FIREBASE_SERVICE_ACCOUNT_PATH=storage/app/firebase-service-account.json|' "$OUT" || true
  sed -i 's|^FIREBASE_PROJECT_ID=.*|FIREBASE_PROJECT_ID=manjiapp-3028e|' "$OUT" || true
  sed -i 's|^CAFEBAZAAR_PACKAGE_NAME=.*|CAFEBAZAAR_PACKAGE_NAME=com.avinpishtazan.manji.cafebazaar|' "$OUT" || true
  sed -i 's|^MYKET_PACKAGE_NAME=.*|MYKET_PACKAGE_NAME=com.avinpishtazan.manji.myket|' "$OUT" || true
  sed -i 's|^SESSION_DRIVER=.*|SESSION_DRIVER=file|' "$OUT" || true
  sed -i 's|^CACHE_STORE=.*|CACHE_STORE=file|' "$OUT" || true
  sed -i 's|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|' "$OUT" || true
  sed -i 's|^CORS_ALLOWED_ORIGINS=.*|CORS_ALLOWED_ORIGINS=https://manjiapp.ir,https://www.manjiapp.ir,https://app.manjiapp.ir,https://admin.manjiapp.ir,https://my.manjiapp.ir|' "$OUT" || true
  sed -i 's|^ADMIN_DASHBOARD_ENFORCE_ORIGIN=.*|ADMIN_DASHBOARD_ENFORCE_ORIGIN=false|' "$OUT" || true

  if ! grep -q '^SESSION_DRIVER=' "$OUT"; then
    echo 'SESSION_DRIVER=file' >> "$OUT"
  fi

  if ! grep -q '^CACHE_STORE=' "$OUT"; then
    echo 'CACHE_STORE=file' >> "$OUT"
  fi

  if ! grep -q '^QUEUE_CONNECTION=' "$OUT"; then
    echo 'QUEUE_CONNECTION=sync' >> "$OUT"
  fi

  if ! grep -q '^FIREBASE_PROJECT_ID=' "$OUT"; then
    echo 'FIREBASE_PROJECT_ID=manjiapp-3028e' >> "$OUT"
  fi

  if ! grep -q '^CAFEBAZAAR_PACKAGE_NAME=' "$OUT"; then
    echo 'CAFEBAZAAR_PACKAGE_NAME=com.avinpishtazan.manji.cafebazaar' >> "$OUT"
  fi

  if ! grep -q '^MYKET_PACKAGE_NAME=' "$OUT"; then
    echo 'MYKET_PACKAGE_NAME=com.avinpishtazan.manji.myket' >> "$OUT"
  fi

  if ! grep -q '^ADMIN_DASHBOARD_URL=' "$OUT"; then
    echo 'ADMIN_DASHBOARD_URL=https://admin.manjiapp.ir' >> "$OUT"
  fi

  if ! grep -q '^CORS_ALLOWED_ORIGINS=' "$OUT"; then
    echo 'CORS_ALLOWED_ORIGINS=https://manjiapp.ir,https://www.manjiapp.ir,https://app.manjiapp.ir,https://admin.manjiapp.ir,https://my.manjiapp.ir' >> "$OUT"
  fi

  if ! grep -q '^ADMIN_DASHBOARD_ENFORCE_ORIGIN=' "$OUT"; then
    echo 'ADMIN_DASHBOARD_ENFORCE_ORIGIN=false' >> "$OUT"
  fi
}

validate_env_file() {
  if ! grep -q '^APP_KEY=.\+' "$OUT"; then
    echo "prepare-production-env: ERROR — APP_KEY is missing"
    exit 1
  fi

  if ! grep -q '^DB_CONNECTION=mysql' "$OUT"; then
    echo "prepare-production-env: ERROR — DB_CONNECTION must be mysql"
    exit 1
  fi

  if ! grep -q '^DB_DATABASE=.\+' "$OUT"; then
    echo "prepare-production-env: ERROR — DB_DATABASE is missing"
    exit 1
  fi

  if ! grep -q '^DB_PASSWORD=.\+' "$OUT"; then
    echo "prepare-production-env: ERROR — DB_PASSWORD is missing"
    exit 1
  fi

  if grep -E '^DB_PASSWORD=[^"\047].*#' "$OUT" >/dev/null 2>&1; then
    echo "prepare-production-env: ERROR — DB_PASSWORD contains # but is not quoted (Laravel would truncate the password)"
    exit 1
  fi

  if grep -E '^MELIPAYAMAK_PASSWORD=[^"\047].*#' "$OUT" >/dev/null 2>&1; then
    echo "prepare-production-env: ERROR — MELIPAYAMAK_PASSWORD contains # but is not quoted (SMS auth will fail)"
    exit 1
  fi

  if ! grep -q '^APP_URL=https://my\.manjiapp\.ir' "$OUT"; then
    echo "prepare-production-env: warning — APP_URL should be https://my.manjiapp.ir for production"
  fi

  if grep -q '^FIREBASE_PROJECT_ID=sarvcast-20d5c' "$OUT"; then
    echo "prepare-production-env: ERROR — FIREBASE_PROJECT_ID must be manjiapp-3028e (found legacy sarvcast-20d5c)"
    exit 1
  fi

  if ! grep -q '^FIREBASE_PROJECT_ID=manjiapp-3028e' "$OUT"; then
    echo "prepare-production-env: ERROR — FIREBASE_PROJECT_ID must be manjiapp-3028e"
    exit 1
  fi
}

download_server_env() {
  local server="${FTP_SERVER:-}"
  local user="${FTP_USERNAME:-}"
  local pass="${FTP_PASSWORD:-}"

  if [ -z "$server" ] || [ -z "$user" ] || [ -z "$pass" ]; then
    return 1
  fi

  if curl -sSf --user "${user}:${pass}" "ftp://${server}/.env" -o "$OUT"; then
    normalize_production_env
    echo "prepare-production-env: downloaded existing server .env via FTP"
    return 0
  fi

  return 1
}

mkdir -p "$(dirname "$OUT")"

if [ -n "${PRODUCTION_DOTENV:-}" ]; then
  printf '%s' "$PRODUCTION_DOTENV" > "$OUT"
  normalize_production_env
  echo "prepare-production-env: wrote .env from PRODUCTION_DOTENV secret"
elif [ "${DOWNLOAD_SERVER_ENV:-}" = "true" ] && download_server_env; then
  if [ -z "${PRODUCTION_DB_PASSWORD:-}" ] && [ -z "${FTP_PASSWORD:-}" ]; then
    echo "prepare-production-env: warning — using server DB_PASSWORD as-is; set PRODUCTION_DB_PASSWORD if migrate fails"
  fi
elif [ -f "$TEMPLATE" ]; then
  APP_KEY="${PRODUCTION_APP_KEY:-}"
  DB_DATABASE="${PRODUCTION_DB_DATABASE:-h352418_sarv}"
  DB_USERNAME="${PRODUCTION_DB_USERNAME:-h352418_sarv}"
  DB_PASSWORD="${PRODUCTION_DB_PASSWORD:-${FTP_PASSWORD:-}}"
  MELIPAYAMAK_USERNAME="${PRODUCTION_MELIPAYAMAK_USERNAME:-09136708883}"
  MELIPAYAMAK_PASSWORD="${PRODUCTION_MELIPAYAMAK_PASSWORD:-${MELIPAYAMAK_PASSWORD:-${FTP_PASSWORD:-}}}"
  CAFEBAZAAR_API_KEY="${PRODUCTION_CAFEBAZAAR_API_KEY:-}"
  MYKET_API_KEY="${PRODUCTION_MYKET_API_KEY:-}"
  ZARINPAL_MERCHANT_ID="${PRODUCTION_ZARINPAL_MERCHANT_ID:-}"

  missing=()
  [ -z "$APP_KEY" ] && missing+=("PRODUCTION_APP_KEY")
  [ -z "$DB_PASSWORD" ] && missing+=("PRODUCTION_DB_PASSWORD or FTP_PASSWORD")

  if [ "${#missing[@]}" -gt 0 ]; then
    echo "prepare-production-env: ERROR — missing values for template fallback:"
    for item in "${missing[@]}"; do
      echo "  - $item"
    done
    echo ""
    echo "Fix (pick one):"
    echo "  1. Set GitHub secret PRODUCTION_DOTENV (full production .env) — recommended"
    echo "     Run: .\\scripts\\set-production-dotenv-github-secret.ps1"
    echo "  2. Set secrets PRODUCTION_APP_KEY + PRODUCTION_DB_PASSWORD"
    echo "  3. Ensure server already has .env so CI can download it via FTP"
    exit 1
  fi

  if [ -z "$CAFEBAZAAR_API_KEY" ] || [ -z "$MYKET_API_KEY" ] || [ -z "$ZARINPAL_MERCHANT_ID" ]; then
    echo "prepare-production-env: warning — payment API keys not in CI secrets; using empty values in template"
  fi

  cp "$TEMPLATE" "$OUT"
  replacements=(
    "__APP_KEY__|$(escape_sed_replacement "$APP_KEY")"
    "__DB_DATABASE__|$(escape_sed_replacement "$DB_DATABASE")"
    "__DB_USERNAME__|$(escape_sed_replacement "$DB_USERNAME")"
    "__DB_PASSWORD__|$(escape_sed_replacement "$DB_PASSWORD")"
    "__MELIPAYAMAK_USERNAME__|$(escape_sed_replacement "$MELIPAYAMAK_USERNAME")"
    "__MELIPAYAMAK_PASSWORD__|$(escape_sed_replacement "$MELIPAYAMAK_PASSWORD")"
    "__CAFEBAZAAR_API_KEY__|$(escape_sed_replacement "$CAFEBAZAAR_API_KEY")"
    "__MYKET_API_KEY__|$(escape_sed_replacement "$MYKET_API_KEY")"
    "__ZARINPAL_MERCHANT_ID__|$(escape_sed_replacement "$ZARINPAL_MERCHANT_ID")"
  )

  for pair in "${replacements[@]}"; do
    key="${pair%%|*}"
    value="${pair#*|}"
    sed -i "s/${key}/${value}/g" "$OUT"
  done

  normalize_production_env
  echo "prepare-production-env: built .env from production template + CI secrets"
else
  echo "prepare-production-env: ERROR — template missing at .github/production.env.template"
  exit 1
fi

normalize_production_env
apply_ci_secret_overrides
ensure_sensitive_env_values_quoted
validate_env_file
echo "prepare-production-env: OK ($(wc -c < "$OUT") bytes)"
