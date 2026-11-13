#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# Respuestas del foro: la lectura es pública, pero PUBLICAR exige sesión.
# El INSERT es preparado y la salida se escapa con e(): ni SQLi ni XSS nuevos,
# la única vuln del foro sigue siendo la RFI de post.php.

# Invitado: ve el aviso de login, NO el formulario de responder.
anon=$(curl -s "$BASE/foro/post.php?id=1")
assert_contains     "$anon" "Inicia sesión para responder" "invitado ve el aviso para responder"
assert_not_contains "$anon" 'name="reply_body"'            "invitado NO ve el formulario de respuesta"

# Miembro con sesión: ve el formulario de responder.
J=$(mktemp)
curl -s -c "$J" -b "$J" -d 'username=mlopez' -d 'password=qwerty' "$BASE/foro/login.php" >/dev/null
member=$(curl -s -b "$J" "$BASE/foro/post.php?id=1")
assert_contains "$member" 'name="reply_body"' "miembro con sesión ve el formulario de respuesta"

# Publicar una respuesta (marcador único) -> aparece en el hilo (lectura pública).
marker="respuesta_test_${RANDOM}${RANDOM}"
curl -s -b "$J" -o /dev/null --data-urlencode "reply_body=$marker" "$BASE/foro/post.php?id=1"
assert_contains "$(curl -s "$BASE/foro/post.php?id=1")" "$marker" "la respuesta publicada aparece en el hilo"

# Escritura gateada: sin sesión NO se inserta la respuesta.
marker_anon="anon_no_debe_${RANDOM}${RANDOM}"
curl -s -o /dev/null --data-urlencode "reply_body=$marker_anon" "$BASE/foro/post.php?id=1"
assert_not_contains "$(curl -s "$BASE/foro/post.php?id=1")" "$marker_anon" "respuesta de invitado rechazada (escritura gateada)"

# Sin XSS almacenado: una respuesta con <script> se devuelve escapada.
curl -s -b "$J" -o /dev/null --data-urlencode 'reply_body=<script>alert(31337)</script>' "$BASE/foro/post.php?id=1"
page=$(curl -s "$BASE/foro/post.php?id=1")
assert_contains     "$page" '&lt;script&gt;alert(31337)&lt;/script&gt;' "la respuesta con <script> se devuelve escapada"
assert_not_contains "$page" '<script>alert(31337)</script>'              "el <script> crudo NO aparece en el HTML"

echo "05_forum_reply OK"
