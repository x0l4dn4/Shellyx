#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# Novedades: feed público de actividad reciente.
nov=$(curl -s "$BASE/foro/novedades.php")
assert_contains     "$nov" "Actividad reciente" "novedades muestra el feed"
assert_contains     "$nov" 'post.php?id='        "novedades enlaza a hilos"
assert_not_contains "$nov" "Fatal error"         "novedades sin fatal"
assert_contains     "$nov" 'href="novedades.php"' "nav enlaza a novedades"
assert_contains     "$nov" 'href="miembros.php"'  "nav enlaza a miembros"

# Miembros: directorio público con enlaces a perfil.
mem=$(curl -s "$BASE/foro/miembros.php")
for u in admin moderador jsanchez mlopez agarcia; do
  assert_contains "$mem" "$u" "miembros lista a $u"
done
assert_contains "$mem" 'perfil.php?u=' "miembros enlaza a perfiles"

echo "12_forum_community OK"
