#!/usr/bin/env bash
set -euo pipefail

# EDIT these 3 lines
GH_OWNER="Farsettalent"
GH_REPO="recruiter-os"
GH_TOKEN="ghp_IdFa4v35psY8ZVh4VyM1quFRRU1Erh07HeSo"
WEBHOOK_URL="https://farsettalent.com/?wppusher-hook&token=9bbb679f887307033d3bf51b61a32f904c80434a5c2108a8cf75a102d41a6f7c&package=ZmFyc2V0LXNhbmRib3g="

# ensure jq is available
if ! command -v jq >/dev/null 2>&1; then
  pkg install -y jq
fi

API="https://api.github.com/repos/$GH_OWNER/$GH_REPO/hooks"
AUTH=(-H "Authorization: token $GH_TOKEN")

# try to find the hook that matches the exact WP Pusher URL
HOOK_ID="$(curl -sS "${AUTH[@]}" "$API" \
  | jq --arg url "$WEBHOOK_URL" -r 'map(select(.config.url == $url)) | .[0].id // empty')"

# if not found, fall back to newest hook
if [ -z "$HOOK_ID" ]; then
  HOOK_ID="$(curl -sS "${AUTH[@]}" "$API" \
    | jq -r 'sort_by(.created_at) | reverse | .[0].id')"
fi

if [ -z "$HOOK_ID" ]; then
  echo "No webhooks found on $GH_OWNER/$GH_REPO"
  exit 1
fi

echo "Using hook id: $HOOK_ID"

# send a ping
curl -sS "${AUTH[@]}" -H "Accept: application/vnd.github+json" \
  -X POST "$API/$HOOK_ID/pings" | jq .

# show last delivery status
echo "Last delivery:"
curl -sS "${AUTH[@]}" "$API/$HOOK_ID/deliveries" \
  | jq -r '.[0] | {status, id, guid, duration_ms}'
