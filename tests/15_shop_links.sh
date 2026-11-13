#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

land=$(curl -s "$BASE/")
assert_contains "$land" 'id="retos"'        "la landing tiene sección Retos mensuales"
assert_contains "$land" 'id="etica"'        "la landing tiene sección Uso ético"
assert_contains "$land" "Retos mensuales"   "la sección de retos tiene contenido"
assert_contains "$land" "Uso ético y legal" "la sección de uso ético tiene contenido"
assert_contains "$land" 'href="/#retos"'    "el footer enlaza a Retos mensuales"
assert_contains "$land" 'href="/#etica"'    "el footer enlaza a Uso ético"

echo "15_shop_links OK"
