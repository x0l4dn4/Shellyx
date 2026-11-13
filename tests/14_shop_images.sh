#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

home=$(curl -s "$BASE/tienda/")
assert_contains "$home" '/assets/img/productos/' "la tienda referencia fotos de producto"
assert_contains "$home" 'class="product__img"'   "las fichas usan <img> de producto"
assert_contains "$home" 'onerror='                "la foto tiene fallback al icono"

# Cada imagen referenciada existe y responde 200.
for slug in shellyx-one shellyx-mini shellyx-pro modulo-wifi-recon modulo-subghz antena-alta-ganancia carcasa-translucida kit-educativo pack-firmwares; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/assets/img/productos/$slug.jpg")
  [ "$code" = "200" ] && pass "imagen $slug.jpg sirve 200" || fail "imagen $slug.jpg -> $code"
done
echo "14_shop_images OK"
