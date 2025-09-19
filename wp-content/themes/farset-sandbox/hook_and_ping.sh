#!/usr/bin/env bash
set -euo pipefail
GH_OWNER="${GH_OWNER:-Farsettalent}"
GH_REPO="${GH_REPO:-recruiter-os}"
GH_TOKEN="${GH_TOKEN:?export GH_TOKEN first}"
WEBHOOK_URL="${WEBHOOK_URL:?export WEBHOOK_URL first}"
UA=(-H "User-Agent: termux")
AUTH=(-H "Authorization: token $GH_TOKEN" -H "Accept: application/vnd.github+json" "${UA[@]}")
API="https://api.github.com/repos/$GH_OWNER/$GH_REPO/hooks"
RAW="$(curl -sS "${AUTH[@]}" "$API")"
if ! echo "$RAW" | jq -e 'type=="array"' >/dev/null 2>&1; then
  echo "GitHub returned (not an array):"; echo "$RAW" | jq .; exit 1
fi
HOOK_ID="$(echo "$RAW" | jq --arg url "$WEBHOOK_URL" -r 'map(select(.config.url==$url)) | .[0].id // empty')"
if [ -z "$HOOK_ID" ]; then
  echo "Creating webhook…"
  CREATE="$(curl -sS "${AUTH[@]}" -X POST "$API" \
    -d "{\"name\":\"web\",\"active\":true,\"events\":[\"push\"],\"config\":{\"url\":\"$WEBHOOK_URL\",\"content_type\":\"json\"}}")"
  if ! echo "$CREATE" | jq -e '.id?'>/dev/null; then
    echo "Create failed:"; echo "$CREATE" | jq .; exit 1
  fi
  HOOK_ID="$(echo "$CREATE" | jq -r '.id')"
fi
echo "Using hook id: $HOOK_ID"
curl -sS "${AUTH[@]}" -X POST "$API/$HOOK_ID/pings" | jq .
echo "Last delivery:"; curl -sS "${AUTH[@]}" "$API/$HOOK_ID/deliveries" | jq -r '.[0] | {status, id, guid, duration_ms}'
