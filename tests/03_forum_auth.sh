#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# login válido -> barra autenticada. "Nueva publicación" y "Salir" son exclusivos
# de sesión iniciada (el invitado no los ve).
J=$(mktemp)
curl -s -c "$J" -b "$J" -d 'username=admin' -d 'password=letmein' "$BASE/foro/login.php" >/dev/null
home_auth=$(curl -s -b "$J" "$BASE/foro/index.php")
assert_contains "$home_auth" "Nueva publicación" "login válido entra al foro (barra autenticada)"
assert_contains "$home_auth" "admin" "la barra muestra el usuario conectado"

# contraseña incorrecta -> rechazada (login.php muestra texto con "incorrect")
Jx=$(mktemp); out=$(curl -s -c "$Jx" -b "$Jx" -d 'username=admin' -d 'password=mala' "$BASE/foro/login.php")
assert_contains "$out" "incorrect" "login inválido rechazado"

# login NO inyectable: una inyección no abre sesión (la barra no muestra "Salir")
Ji=$(mktemp); curl -s -c "$Ji" -b "$Ji" --data-urlencode "username=admin'-- -" -d 'password=x' "$BASE/foro/login.php" >/dev/null
assert_not_contains "$(curl -s -b "$Ji" "$BASE/foro/index.php")" "Salir" "SQLi en login NO autentica"

# registro deshabilitado
assert_contains "$(curl -s "$BASE/foro/register.php")" "Acceso solo por invitación" "registro solo invitación"

# LECTURA PÚBLICA: el índice se ve SIN sesión (no redirige a login)
pub_home=$(curl -s "$BASE/foro/index.php")
assert_contains "$pub_home" "Firmware y Desarrollo" "índice visible sin sesión (lectura pública)"
assert_not_contains "$pub_home" "Nueva publicación" "invitado no ve la acción de publicar"

# LECTURA PÚBLICA: un hilo se ve SIN sesión
assert_contains "$(curl -s "$BASE/foro/post.php?id=1")" "Shellyx Nibbler" "post visible sin sesión (lectura pública)"

echo "03_forum_auth OK"
