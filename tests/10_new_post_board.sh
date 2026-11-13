#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

dbq() { docker compose exec -T db mariadb -uappuser -papppass appdb -N -e "$1" 2>/dev/null | tr -d '[:space:]'; }
crear() { # <title> <board|''> ; imprime el id del hilo creado
  local loc
  loc=$(curl -s -b "$J" -D - -o /dev/null -d "title=$1" -d 'body=b' -d "board=$2" --data-urlencode 'embed_url=' \
    "$BASE/foro/new_post.php" | grep -i '^location:' | tr -d '\r' | awk '{print $2}')
  printf '%s' "$loc" | grep -oE '[0-9]+$'
}

J=$(mktemp)
curl -s -c "$J" -b "$J" -d 'username=admin' -d 'password=letmein' "$BASE/foro/login.php" >/dev/null

# El formulario ofrece el selector de tablón, con categorías y dispositivos.
form=$(curl -s -b "$J" "$BASE/foro/new_post.php")
assert_contains "$form" 'name="board"'   "new_post tiene selector de tablón"
assert_contains "$form" 'value="dev:3"'   "el selector incluye el dispositivo 3"
assert_contains "$form" 'value="cat:firmware"' "el selector incluye la categoría firmware"

# Crear hilo en un dispositivo -> product_id = 3.
nid=$(crear 'Hilo+dev' 'dev:3')
pid=$(dbq "SELECT product_id FROM posts WHERE id=$nid")
[ "$pid" = "3" ] && pass "hilo creado con product_id=3" || fail "product_id esperado 3, got '$pid'"

# board=dev:999 (inexistente) -> no crea product_id huérfano; cae a categoría; 200.
nid2=$(crear 'Hilo+malo' 'dev:999')
code=$(curl -s -b "$J" -o /dev/null -w '%{http_code}' "$BASE/foro/post.php?id=$nid2")
pid2=$(dbq "SELECT IFNULL(product_id,'NULL') FROM posts WHERE id=$nid2")
{ [ "$code" = "200" ] && [ "$pid2" = "NULL" ]; } && pass "board=dev:999 cae a categoría (product_id NULL), 200" || fail "board inválido: code=$code product_id=$pid2"

# board manipulado (no casa dev:<num>) -> product_id NULL (sin inyección).
nid3=$(curl -s -b "$J" -D - -o /dev/null -d 'title=inj' -d 'body=b' --data-urlencode 'board=dev:1 OR 1=1' --data-urlencode 'embed_url=' "$BASE/foro/new_post.php" | grep -i '^location:' | tr -d '\r' | awk '{print $2}' | grep -oE '[0-9]+$')
pid3=$(dbq "SELECT IFNULL(product_id,'NULL') FROM posts WHERE id=$nid3")
[ "$pid3" = "NULL" ] && pass "board manipulado -> product_id NULL" || fail "board manipulado: product_id=$pid3"

echo "10_new_post_board OK"
