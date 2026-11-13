#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# Sin sesión: el carrito y el checkout redirigen al login de la tienda.
assert_contains "$(curl -s -L "$BASE/tienda/carrito.php")"  "Accede a tu cuenta" "carrito sin sesión -> login de tienda"
assert_contains "$(curl -s -L "$BASE/tienda/checkout.php")" "Accede a tu cuenta" "checkout sin sesión -> login de tienda"

# Registro abierto: crea cuenta e inicia sesión; el carrito queda accesible.
U="cli$RANDOM$RANDOM"
J=$(mktemp)
curl -s -c "$J" -b "$J" -o /dev/null -d "username=$U" -d 'password=secreto123' "$BASE/tienda/registro.php"
assert_not_contains "$(curl -s -b "$J" "$BASE/tienda/carrito.php")" "Accede a tu cuenta" "tras registro, el carrito es accesible"

# Registro duplicado -> rechazado.
assert_contains "$(curl -s -d "username=$U" -d 'password=secreto123' "$BASE/tienda/registro.php")" "ya existe" "registro con usuario existente rechazado"

# Login con la cuenta recién creada.
J2=$(mktemp); curl -s -c "$J2" -b "$J2" -o /dev/null -d "username=$U" -d 'password=secreto123' "$BASE/tienda/login.php"
assert_not_contains "$(curl -s -b "$J2" "$BASE/tienda/carrito.php")" "Accede a tu cuenta" "login de tienda da acceso al carrito"

# Login NO inyectable (sentencia preparada).
Ji=$(mktemp); curl -s -c "$Ji" -b "$Ji" -o /dev/null --data-urlencode "username=x' OR '1'='1" -d 'password=x' "$BASE/tienda/login.php"
assert_contains "$(curl -s -L -b "$Ji" "$BASE/tienda/carrito.php")" "Accede a tu cuenta" "SQLi en login de tienda NO autentica"

# Nav: con sesión aparece el enlace de salir.
assert_contains "$(curl -s -b "$J2" "$BASE/tienda/")" "logout.php" "la nav muestra Salir con sesión de tienda"

echo "17_shop_auth OK"
