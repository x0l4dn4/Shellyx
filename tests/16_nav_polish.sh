#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# Fix 1: el autor del hilo (meta "iniciado por") enlaza a su perfil.
p1=$(curl -s "$BASE/foro/post.php?id=1")
assert_contains "$p1" 'iniciado por <a href="perfil.php?u=admin"' "autor del hilo enlaza a su perfil"

# Fix 2: breadcrumb de categoría -> categoria.php; de dispositivo -> dispositivo.php.
assert_contains "$p1" 'categoria.php?c=firmware' "breadcrumb de categoría enlaza a categoria.php"
pd=$(curl -s "$BASE/foro/post.php?id=12")
assert_contains "$pd" 'dispositivo.php?id=1' "breadcrumb de hilo de dispositivo enlaza al subforo"

# Fix 3: el precio en el foro conserva los decimales (como la tienda).
assert_contains "$(curl -s "$BASE/foro/dispositivo.php?id=3")" "149.00" "el precio del foro conserva decimales"

echo "16_nav_polish OK"
