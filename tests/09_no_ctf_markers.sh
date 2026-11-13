#!/usr/bin/env bash
set -u
cd "$(dirname "$0")/.." || exit 1
source tests/lib.sh

# El código servido (web/src) y el seed no deben delatar las vulnerabilidades ni
# que esto es un laboratorio/CTF.
PAT='vulnerabilid|deliberad|inyect|\bRFI\b|\bSQLi\b|\bCTF\b|del reto|laborator|rockyou|allow_url_include|salto tienda|cracke|el vector|UNION-vulnerable'
# Se excluye el fichero de atribución de las fotos (contiene nombres de fichero de
# Wikimedia Commons, no comentarios del código).
hits=$(grep -rniE "$PAT" web/src db/init 2>/dev/null | grep -vF 'productos/SOURCES.txt')
if [ -n "$hits" ]; then
  echo "$hits"
  fail "quedan marcadores de CTF/vulnerabilidad en el código servido"
else
  pass "sin marcadores de CTF/vulnerabilidad en web/src y db/init"
fi
echo "09_no_ctf_markers OK"
