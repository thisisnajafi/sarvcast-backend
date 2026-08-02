#!/usr/bin/env bash
set -euo pipefail

# curl wrapper for https://my.manjiapp.ir deploy endpoints.
# Hosting uses a self-signed / incomplete cert chain, so verification is skipped.
#
# Usage: curl-site.sh [curl args...] <url>
# Example: curl-site.sh -fsS --max-time 60 "https://my.manjiapp.ir/_deploy_helper.php?..."

if [[ $# -lt 1 ]]; then
  echo "curl-site.sh: URL required" >&2
  exit 2
fi

URL="${@: -1}"
ARGS=("${@:1:$#-1}")

exec curl \
  --insecure \
  --location \
  --connect-timeout 30 \
  "${ARGS[@]}" \
  "$URL"
