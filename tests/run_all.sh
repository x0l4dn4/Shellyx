#!/usr/bin/env bash
# Ejecuta la cadena completa de tests de integración en orden.
# Requisito: el stack levantado (docker compose up -d --build) y, para el
# paso RFI, el host permitiendo tráfico contenedor->host (ver README).
set -e
cd "$(dirname "$0")/.."
for t in 00_stack_up 01_shop_catalog 02_sqli_union 03_forum_auth 04_posts_rfi; do
  echo "== $t =="
  bash "tests/$t.sh"
done
echo "CADENA COMPLETA OK"
