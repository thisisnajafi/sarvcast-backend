#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/deploy-upload/.env"

if [ -z "${PRODUCTION_DOTENV:-}" ]; then
  echo "prepare-production-env: ERROR — PRODUCTION_DOTENV GitHub secret is not set."
  echo "Run scripts/set-production-dotenv-github-secret.ps1 locally, or add the secret manually:"
  echo "  https://github.com/thisisnajafi/sarvcast-backend/settings/secrets/actions"
  exit 1
fi

mkdir -p "$(dirname "$OUT")"
printf '%s' "$PRODUCTION_DOTENV" > "$OUT"

if ! grep -q '^APP_KEY=.\+' "$OUT"; then
  echo "prepare-production-env: ERROR — APP_KEY is missing in PRODUCTION_DOTENV"
  exit 1
fi

if ! grep -q '^DB_CONNECTION=mysql' "$OUT"; then
  echo "prepare-production-env: ERROR — DB_CONNECTION must be mysql in PRODUCTION_DOTENV"
  exit 1
fi

if ! grep -q '^DB_DATABASE=.\+' "$OUT"; then
  echo "prepare-production-env: ERROR — DB_DATABASE is missing in PRODUCTION_DOTENV"
  exit 1
fi

if ! grep -q '^APP_URL=https://my\.manjiapp\.ir' "$OUT"; then
  echo "prepare-production-env: warning — APP_URL should be https://my.manjiapp.ir for production"
fi

echo "prepare-production-env: OK ($(wc -c < "$OUT") bytes)"
