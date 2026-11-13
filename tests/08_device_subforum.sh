#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

dbq() { docker compose exec -T db mariadb -uappuser -papppass appdb -N -e "$1" 2>/dev/null | tr -d '[:space:]'; }

# --- Esquema y seed ---
col=$(dbq "SHOW COLUMNS FROM posts LIKE 'product_id'")
assert_contains "$col" "product_id" "posts tiene columna product_id"
n=$(dbq "SELECT COUNT(*) FROM posts WHERE product_id IS NOT NULL")
if [ "${n:-0}" -ge 1 ]; then pass "hay hilos de dispositivo sembrados ($n)"; else fail "no hay hilos con product_id (n=${n:-0})"; fi

# --- Subforo de dispositivo (lectura pública) ---
page=$(curl -s "$BASE/foro/dispositivo.php?id=3")
assert_contains "$page" "Shellyx Pro"   "dispositivo 3 muestra el nombre del producto"
assert_contains "$page" "GPIO del Pro"  "dispositivo 3 lista su hilo sembrado"

# id inexistente / no numérico: estado limpio, sin warning.
p999=$(curl -s "$BASE/foro/dispositivo.php?id=999")
assert_contains     "$p999" "no encontrado" "id inexistente -> no encontrado"
assert_not_contains "$p999" "Warning"        "id inexistente sin warning"
pabc=$(curl -s "$BASE/foro/dispositivo.php?id=abc")
assert_not_contains "$pabc" "Warning"        "id no numérico sin warning"

# Review Focus: un hilo con <script> en el título se devuelve escapado.
J=$(mktemp); curl -s -c "$J" -b "$J" -d 'username=mlopez' -d 'password=qwerty' "$BASE/foro/login.php" >/dev/null
curl -s -b "$J" -o /dev/null -d 'title=<script>alert(9)</script>' -d 'body=b' -d 'board=dev:3' --data-urlencode 'embed_url=' "$BASE/foro/new_post.php"
page=$(curl -s "$BASE/foro/dispositivo.php?id=3")
assert_contains     "$page" '&lt;script&gt;alert(9)&lt;/script&gt;' "título con <script> escapado en el subforo"
assert_not_contains "$page" '<script>alert(9)</script>'             "el <script> crudo no aparece"

echo "08_device_subforum OK"
