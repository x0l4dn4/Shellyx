#!/usr/bin/env bash
# Helpers compartidos para los tests de integración del CTF.
# Uso: source tests/lib.sh   (desde la raíz del proyecto)

BASE="${BASE:-http://localhost:8080}"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

pass() { echo "PASS: $1"; }
fail() { echo "FAIL: $1" >&2; exit 1; }

# assert_contains <haystack> <needle> <nombre>
assert_contains() {
  if printf '%s' "$1" | grep -qF -- "$2"; then
    pass "$3"
  else
    fail "$3 (no se encontró: $2)"
  fi
}

# assert_not_contains <haystack> <needle> <nombre>
assert_not_contains() {
  if printf '%s' "$1" | grep -qF -- "$2"; then
    fail "$3 (no debía contener: $2)"
  else
    pass "$3"
  fi
}
