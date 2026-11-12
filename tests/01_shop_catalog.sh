#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

assert_contains "$(curl -s "$BASE/tienda/")" "Shellyx One" "catálogo lista un producto"
echo "01_shop_catalog OK"
