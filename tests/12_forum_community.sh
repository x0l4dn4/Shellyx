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

# Perfil (lectura pública): hilos del usuario.
prof=$(curl -s "$BASE/foro/perfil.php?u=admin")
assert_contains     "$prof" "Hilos de admin" "perfil muestra los hilos del usuario"
assert_not_contains "$prof" "Fatal error"    "perfil sin fatal"

# Usuario inexistente: estado limpio.
pn=$(curl -s "$BASE/foro/perfil.php?u=nope")
assert_contains     "$pn" "no encontrado" "perfil de usuario inexistente -> no encontrado"
assert_not_contains "$pn" "Warning"        "perfil inexistente sin warning"

# Review Focus: contenido del usuario con <script> se escapa.
J=$(mktemp); curl -s -c "$J" -b "$J" -d 'username=agarcia' -d 'password=sunshine' "$BASE/foro/login.php" >/dev/null
curl -s -b "$J" -o /dev/null -d 'title=<script>alert(8)</script>' -d 'body=b' -d 'board=cat:general' --data-urlencode 'embed_url=' "$BASE/foro/new_post.php"
prof2=$(curl -s "$BASE/foro/perfil.php?u=agarcia")
assert_contains     "$prof2" '&lt;script&gt;alert(8)&lt;/script&gt;' "título con <script> escapado en el perfil"
assert_not_contains "$prof2" '<script>alert(8)</script>'             "el <script> crudo no aparece en el perfil"

# La barra enlaza el chip de sesión al propio perfil.
assert_contains "$(curl -s -b "$J" "$BASE/foro/index.php")" 'perfil.php?u=agarcia' "el chip de sesión enlaza al propio perfil"

echo "12_forum_community OK"
