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

# GitHub retirado de nav y footer; Docs presente y servible.
assert_not_contains "$land" "github.com"     "la tienda ya no enlaza a GitHub"
assert_not_contains "$land" "GitHub"          "la landing no menciona GitHub en el texto"
assert_contains     "$land" 'href="/docs/"'  "nav/footer enlazan a Docs"
docs=$(curl -s "$BASE/docs/")
assert_contains     "$docs" "Documentación"  "la página Docs responde con contenido"

echo "15_shop_links OK"
