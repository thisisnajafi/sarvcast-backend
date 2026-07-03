#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGING="$ROOT/deploy-staging"
UPLOAD="$ROOT/deploy-upload"
TOKEN="${DEPLOY_EXTRACT_TOKEN:-manji-ftp-deploy-x7k9m2}"

rm -rf "$STAGING" "$UPLOAD"
mkdir -p "$STAGING" "$UPLOAD"

echo "prepare-ftp-deploy: staging application files..."

rsync -a \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  --exclude='tests/' \
  --exclude='deploy-staging/' \
  --exclude='deploy-upload/' \
  --exclude='storage/logs/' \
  --exclude='storage/framework/' \
  --exclude='storage/app/public/' \
  --exclude='bootstrap/cache/*.php' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='vendor.zip' \
  --exclude='deploy.tar.gz' \
  --exclude='deploy.zip' \
  --exclude='docs/' \
  --exclude='storage/app/manji-stories/' \
  --exclude='storage/app/*-firebase-adminsdk*.json' \
  "$ROOT/" "$STAGING/"

mkdir -p "$STAGING/storage/app"
FIREBASE_DEST="$STAGING/storage/app/firebase-service-account.json"

if [ -n "${FIREBASE_SERVICE_ACCOUNT_JSON:-}" ]; then
  printf '%s' "$FIREBASE_SERVICE_ACCOUNT_JSON" > "$FIREBASE_DEST"
  echo "prepare-ftp-deploy: wrote firebase-service-account.json from CI secret"
elif [ -f "$ROOT/storage/app/firebase-service-account.json" ]; then
  cp "$ROOT/storage/app/firebase-service-account.json" "$FIREBASE_DEST"
  echo "prepare-ftp-deploy: bundled existing firebase-service-account.json"
else
  for candidate in "$ROOT"/storage/app/*-firebase-adminsdk*.json; do
    if [ -f "$candidate" ]; then
      cp "$candidate" "$FIREBASE_DEST"
      echo "prepare-ftp-deploy: bundled $(basename "$candidate") as firebase-service-account.json"
      break
    fi
  done
fi

if [ ! -f "$FIREBASE_DEST" ]; then
  if [ "${ALLOW_MISSING_FIREBASE_BUNDLE:-}" = "true" ]; then
    echo "prepare-ftp-deploy: warning — no Firebase JSON in bundle (server may already have storage/app/firebase-service-account.json)"
  else
    echo "prepare-ftp-deploy: ERROR — no Firebase service account in bundle"
    echo "Set GitHub secret FIREBASE_SERVICE_ACCOUNT_JSON (full service account JSON),"
    echo "or set ALLOW_MISSING_FIREBASE_BUNDLE=true if the file already exists on the server."
    exit 1
  fi
fi

if [ ! -f "$ROOT/vendor.zip" ]; then
  echo "prepare-ftp-deploy: ERROR — vendor.zip missing (run composer install + zip first)"
  exit 1
fi

cp "$ROOT/vendor.zip" "$UPLOAD/vendor.zip"

mkdir -p "$UPLOAD/public"
cp "$ROOT/public/_deploy_helper.php" "$UPLOAD/public/_deploy_helper.php"

if [ -f "$ROOT/htaccess" ]; then
  cp "$ROOT/htaccess" "$STAGING/htaccess.deploy"
  cp "$ROOT/htaccess" "$UPLOAD/htaccess.deploy"
fi

if [ -f "$ROOT/public/htaccess" ]; then
  cp "$ROOT/public/htaccess" "$STAGING/public/htaccess.deploy"
  cp "$ROOT/public/htaccess" "$UPLOAD/public/htaccess.deploy"
fi

(
  cd "$STAGING"
  find . -type f ! -path './deploy-manifest.txt' | sed 's|^\./||' | sort > deploy-manifest.txt
)

echo "prepare-ftp-deploy: creating deploy.zip..."
(
  cd "$STAGING"
  zip -r -q -9 "$UPLOAD/deploy.zip" .
)

TEMPLATE="$ROOT/scripts/extract-deploy.php"
DEST="$UPLOAD/public/extract-deploy.php"
if [ ! -f "$TEMPLATE" ]; then
  echo "prepare-ftp-deploy: ERROR — scripts/extract-deploy.php missing"
  exit 1
fi

sed "s/__DEPLOY_EXTRACT_TOKEN__/${TOKEN//\//\\/}/" "$TEMPLATE" > "$DEST"

ZIP_MB=$(du -m "$UPLOAD/deploy.zip" | awk '{print $1}')
VENDOR_MB=$(du -m "$UPLOAD/vendor.zip" | awk '{print $1}')
FILE_COUNT=$(unzip -l "$UPLOAD/deploy.zip" | tail -n 1 | awk '{print $2}')

echo "prepare-ftp-deploy: deploy.zip ${ZIP_MB} MB (${FILE_COUNT} entries)"
echo "prepare-ftp-deploy: vendor.zip ${VENDOR_MB} MB"
echo "prepare-ftp-deploy: ready in deploy-upload/"
