#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# Esquema: posts tiene la columna product_id.
col=$(docker compose exec -T db mariadb -uappuser -papppass appdb -N -e "SHOW COLUMNS FROM posts LIKE 'product_id';" 2>/dev/null)
assert_contains "$col" "product_id" "posts tiene columna product_id"

# Seed: hay hilos de dispositivo (product_id no nulo).
n=$(docker compose exec -T db mariadb -uappuser -papppass appdb -N -e "SELECT COUNT(*) FROM posts WHERE product_id IS NOT NULL;" 2>/dev/null | tr -d '[:space:]')
if [ "${n:-0}" -ge 1 ]; then pass "hay hilos de dispositivo sembrados ($n)"; else fail "no hay hilos con product_id (n=${n:-0})"; fi

echo "08_device_subforum OK"
