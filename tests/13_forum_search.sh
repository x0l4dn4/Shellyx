#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# Búsqueda normal encuentra el hilo.
r=$(curl -s --get --data-urlencode 'q=Nibbler' "$BASE/foro/buscar.php")
assert_contains "$r" "Shellyx Nibbler" "búsqueda 'Nibbler' encuentra el hilo"

# Review Focus: una inyección no rompe ni vuelca todos los hilos (preparada).
inj=$(curl -s --get --data-urlencode "q=' OR 1=1-- -" "$BASE/foro/buscar.php")
assert_contains     "$inj" "resultado"        "la búsqueda inyectada renderiza normal"
assert_not_contains "$inj" "Shellyx Nibbler"  "la inyección NO vuelca todos los hilos"
assert_not_contains "$inj" "Warning"          "la inyección no genera warning"

# La barra del foro usa un <form> hacia buscar.php y hay favicon.
bar=$(curl -s "$BASE/foro/")
assert_contains "$bar" 'action="buscar.php"' "la barra busca vía form a buscar.php"
assert_contains "$bar" 'rel="icon"'          "forum_head incluye favicon"

echo "13_forum_search OK"
