#!/usr/bin/env bash
set -euo pipefail

# Usage: ci-call-deploy-helper.sh <site_url> <deploy_token> <query_string> <label> [max_time_seconds]
SITE_URL="${1:?site url required}"
DEPLOY_TOKEN="${2:?deploy token required}"
QUERY="${3:?query string required}"
LABEL="${4:?label required}"
MAX_TIME="${5:-300}"

URL="${SITE_URL}/_deploy_helper.php?token=${DEPLOY_TOKEN}&${QUERY}"

echo "::group::${LABEL}"
set +e
RESPONSE=$(curl -sS -w "\n%{http_code}" "${URL}" --max-time "${MAX_TIME}" --connect-timeout 30)
CURL_EXIT=$?
set -e

if [[ "$CURL_EXIT" -ne 0 ]]; then
  echo "::error::Could not reach deploy helper (${LABEL}, curl exit ${CURL_EXIT})"
  echo "::endgroup::"
  exit 1
fi

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')
echo "HTTP status: ${HTTP_CODE}"
echo "Raw response: ${BODY}"

if echo "$BODY" | grep -qE 'Fatal error|Parse error|memory size of .* exhausted'; then
  echo "::error::PHP fatal error in deploy helper response (${LABEL})"
  echo "::endgroup::"
  exit 1
fi

echo "Command results:"
echo "$BODY" | jq -r '.results[]?' 2>/dev/null | while read -r line; do
  echo "  - $line"
done

STATUS=$(echo "$BODY" | jq -r '.status // "unknown"')

if [[ "$HTTP_CODE" != "200" ]] || [[ "$STATUS" != "success" ]]; then
  echo "::error::${LABEL} failed"
  echo "::endgroup::"
  exit 1
fi

echo "✅ ${LABEL} completed"
echo "::endgroup::"
