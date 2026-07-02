#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/deploy-upload/.env"
TEMPLATE="$ROOT/.github/production.env.template"

escape_sed_replacement() {
  printf '%s' "$1" | sed -e 's/[\/&]/\\&/g'
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

  if ! grep -q '^APP_URL=https://my\.manjiapp\.ir' "$OUT"; then
    echo "prepare-production-env: warning — APP_URL should be https://my.manjiapp.ir for production"
  fi
}

mkdir -p "$(dirname "$OUT")"

if [ -n "${PRODUCTION_DOTENV:-}" ]; then
  printf '%s' "$PRODUCTION_DOTENV" > "$OUT"
  echo "prepare-production-env: wrote .env from PRODUCTION_DOTENV secret"
else
  if [ ! -f "$TEMPLATE" ]; then
    echo "prepare-production-env: ERROR — template missing at .github/production.env.template"
    exit 1
  fi

  APP_KEY="${PRODUCTION_APP_KEY:-${APP_KEY:-}}"
  DB_DATABASE="${PRODUCTION_DB_DATABASE:-h352418_sarv}"
  DB_USERNAME="${PRODUCTION_DB_USERNAME:-h352418_sarv}"
  DB_PASSWORD="${PRODUCTION_DB_PASSWORD:-}"
  MELIPAYAMAK_USERNAME="${PRODUCTION_MELIPAYAMAK_USERNAME:-09136708883}"
  MELIPAYAMAK_PASSWORD="${PRODUCTION_MELIPAYAMAK_PASSWORD:-${MELIPAYAMAK_PASSWORD:-${FTP_PASSWORD:-}}}"
  CAFEBAZAAR_API_KEY="${PRODUCTION_CAFEBAZAAR_API_KEY:-}"
  MYKET_API_KEY="${PRODUCTION_MYKET_API_KEY:-}"
  ZARINPAL_MERCHANT_ID="${PRODUCTION_ZARINPAL_MERCHANT_ID:-}"

  missing=()
  [ -z "$APP_KEY" ] && missing+=("PRODUCTION_APP_KEY or APP_KEY")
  [ -z "$DB_PASSWORD" ] && missing+=("PRODUCTION_DB_PASSWORD (or set GitHub secret PRODUCTION_DOTENV with full .env)")
  [ -z "$CAFEBAZAAR_API_KEY" ] && missing+=("PRODUCTION_CAFEBAZAAR_API_KEY")
  [ -z "$MYKET_API_KEY" ] && missing+=("PRODUCTION_MYKET_API_KEY")
  [ -z "$ZARINPAL_MERCHANT_ID" ] && missing+=("PRODUCTION_ZARINPAL_MERCHANT_ID")

  if [ "${#missing[@]}" -gt 0 ]; then
    echo "prepare-production-env: ERROR — missing values for template fallback:"
    for item in "${missing[@]}"; do
      echo "  - $item"
    done
    echo "Either set GitHub secret PRODUCTION_DOTENV (full .env), or add the values above to workflow env / secrets."
    exit 1
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

  echo "prepare-production-env: built .env from production template + workflow env"
fi

validate_env_file
echo "prepare-production-env: OK ($(wc -c < "$OUT") bytes)"
