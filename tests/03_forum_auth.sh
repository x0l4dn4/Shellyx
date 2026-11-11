#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

J=$(mktemp)
# login válido
curl -s -c "$J" -b "$J" -d 'username=admin' -d 'password=letmein' "$BASE/foro/login.php" >/dev/null
assert_contains "$(curl -s -b "$J" "$BASE/foro/index.php")" "admin" "login válido entra al foro"

# Review Focus #5: contraseña incorrecta -> rechazada (login.php muestra texto con "incorrect")
Jx=$(mktemp); out=$(curl -s -c "$Jx" -b "$Jx" -d 'username=admin' -d 'password=mala' "$BASE/foro/login.php")
assert_contains "$out" "incorrect" "login inválido rechazado"

# Review Focus #1: login NO inyectable; sin sesión, index redirige a login
Ji=$(mktemp); curl -s -c "$Ji" -b "$Ji" --data-urlencode "username=admin'-- -" -d 'password=x' "$BASE/foro/login.php" >/dev/null
assert_contains "$(curl -s -b "$Ji" -L "$BASE/foro/index.php")" "Iniciar sesión" "SQLi en login NO autentica"

# registro deshabilitado
assert_contains "$(curl -s "$BASE/foro/register.php")" "Acceso solo por invitación" "registro solo invitación"

# Review Focus #2: index sin sesión redirige a login
assert_contains "$(curl -s -L "$BASE/foro/index.php")" "Iniciar sesión" "foro exige login"

echo "03_forum_auth OK"
