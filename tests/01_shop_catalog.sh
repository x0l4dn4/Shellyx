#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

assert_contains "$(curl -s "$BASE/tienda/")" "Camiseta Demo" "catálogo lista producto demo"
echo "01_shop_catalog OK"
