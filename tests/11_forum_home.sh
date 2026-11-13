#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

home=$(curl -s "$BASE/foro/")
assert_contains "$home" "Explora nuestra tienda" "la home tiene la caja 'Explora nuestra tienda'"
assert_contains "$home" 'href="/tienda/"'        "la caja enlaza a la tienda"
assert_contains "$home" "Nuevos posts"           "la home tiene 'Nuevos posts'"
assert_contains "$home" "Firmware y Desarrollo"  "la home muestra las categorías"
assert_contains "$home" 'dispositivo.php?id='    "la home enlaza a subforos de dispositivo"
assert_contains "$home" 'categoria.php?c='       "la home enlaza a las categorías"

# Subforo de categoría (destino de las tarjetas): lectura pública.
cat=$(curl -s "$BASE/foro/categoria.php?c=firmware")
assert_contains "$cat" "Firmware y Desarrollo" "categoria.php?c=firmware muestra la categoría"

echo "11_forum_home OK"
