#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

body=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/")
[ "$body" = "200" ] || fail "landing responde 200"
assert_contains "$(curl -s "$BASE/")" "CTF" "landing contiene marcador"
ini=$(docker compose exec -T web php -r 'echo ini_get("allow_url_include");')
[ "$ini" = "1" ] || fail "allow_url_include=On"
echo "00_stack_up OK"
