#!/usr/bin/env bash
set -e
git add -A
git commit -m "${1:-update}"
git push
./hook_and_ping.sh
