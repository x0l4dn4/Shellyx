# Foro XDA + subforos por producto, comunidad y pulido de tienda — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rehacer la portada del foro al estilo XDA con un subforo por producto, completar las páginas de comunidad y pulir la tienda, y borrar del código servido todo rastro de que es una CTF, sin cambiar el comportamiento vulnerable.

**Architecture:** App PHP 7.4 + MariaDB servida por Docker (bind-mount de `web/src`: los `.php` se ven en vivo; el esquema exige re-seed). El foro reutiliza `foro/lib/layout.php` + `foro/assets/forum.css`; la tienda `lib/layout.php` + `assets/css/shellyx.css`. Los hilos ganan una pertenencia opcional a producto (`posts.product_id`). Páginas nuevas y consultas nuevas: siempre sentencias preparadas + salida escapada con `e()`.

**Tech Stack:** PHP 7.4 (mysqli), MariaDB 10.11, HTML/CSS sin frameworks, tests de integración en bash+curl (`tests/*.sh`, `tests/lib.sh`).

**Spec:** `docs/superpowers/specs/2026-11-13-foro-xda-comunidad-tienda-design.md`

## Global Constraints

- **Única inyección SQL del laboratorio: el buscador de la tienda** (`tienda/search.php`). Toda consulta nueva va con `mysqli_prepare` + `bind_param`. El foro NO debe ser inyectable.
- **Única inclusión del foro: `embed_url` en `post.php`**, acotada a `^https?://`. Nada de LFI/wrappers nuevos.
- **Salida de datos de BD siempre por `e()`** (htmlspecialchars). Sin XSS nuevo.
- **El scrub es textual**: no cambia lógica ni comportamiento vulnerable (SQLi tienda, RFI, MD5 intactos).
- **Rutas absolutas** desde DocumentRoot para assets compartidos (`/assets/...`), relativas dentro de `/foro/`.
- **Diseño**: reutilizar tokens y clases existentes de `forum.css`/`shellyx.css`; nada de rediseño ni librerías nuevas.
- **Agrupación de dispositivos por gama** (misma en portada y selector): entrada=[1,2], avanzada=[3], módulos=[4,5,6,7], packs=[8,9].
- **Entrega**: `branch-lino` (ya a nivel de `main`), commits temáticos, PR a `main`. Las líneas de atribución de commits/PR según el recordatorio vigente de la sesión.

## Review Focus

- **Buscador del foro con comilla/metacaracteres** (`q=' OR 1=1-- -`): responde normal, sin error SQL ni filas de más. → test en Task 9.
- **Datos con HTML** (`<script>` en título de hilo, respuesta, perfil o subforo de dispositivo): se devuelven escapados, nunca ejecutables. → tests en Task 5 (dispositivo) y Task 8 (perfil).
- **Selector de tablón manipulado** (`board=dev:999` inexistente, `board=cat:bogus`, `board=dev:1 OR 1=1`): se valida contra productos/categorías reales; sin error, sin `product_id` huérfano, sin inyección. → test en Task 4.
- **`dispositivo.php` con id no numérico o inexistente**: 404/estado limpio, sin warning PHP. → test en Task 5.
- **`embed_url` no-http** (`test`, `/etc/passwd`, `php://filter/...`): sin warning y sin inclusión local; solo `http(s)` se incluye. → test en Task 2.

---

### Task 1: Esquema y seed de subforos de dispositivo

**Files:**
- Modify: `db/init/01-schema-seed.sql`
- Test: `tests/08_device_subforum.sh` (crear)

**Interfaces:**
- Produces: columna `posts.product_id INT NULL` + `INDEX idx_posts_product(product_id)`; hilos sembrados con `product_id` para varios productos. Consumido por Tasks 4, 5, 6.

- [ ] **Step 1 — Test que falla:** `tests/08_device_subforum.sh` consulta vía web que existen hilos de dispositivo. Como aún no hay página, el test de este task valida a nivel BD con `docker compose exec -T db`: `assert` que `SELECT COUNT(*) FROM posts WHERE product_id IS NOT NULL` ≥ 1 y que `SHOW COLUMNS FROM posts LIKE 'product_id'` devuelve fila.
- [ ] **Step 2 — Ejecutar y ver fallar** (`bash tests/08_device_subforum.sh`): FALLA (columna no existe).
- [ ] **Step 3 — Implementar** en el seed: añadir `product_id INT NULL` a la definición de `posts` y `INDEX idx_posts_product (product_id)`; tras los INSERT de hilos existentes, sembrar 1–2 hilos por producto (`product_id` fijado, `category` en su DEFAULT), contenido on-theme, con alguna respuesta. Reutiliza las variables `@admin`,`@ml`, etc.
- [ ] **Step 4 — Re-seed y ver pasar:** `docker compose down -v && docker compose up -d --build`; esperar sano; `bash tests/08_device_subforum.sh`: PASA.
- [ ] **Step 5 — Commit** (`feat: subforos de dispositivo (posts.product_id + seed)`).

### Task 2: Inclusión acotada a http(s) + scrub de post.php

**Files:**
- Modify: `web/src/foro/post.php` (bloque `include`, líneas ~80-85, y comentarios ~5,11,19-21)
- Test: `tests/04_posts_rfi.sh` (extender)

**Interfaces:**
- Produces: `post.php` incluye `embed_url` solo si casa `~^https?://~i`; comentarios neutros.

- [ ] **Step 1 — Test que falla:** en `tests/04_posts_rfi.sh` añadir: crear post con `embed_url=test`; `assert_not_contains` de la respuesta el texto `Warning` y `assert` HTTP 200. (Y conservar el test http:// que sí ejecuta.)
- [ ] **Step 2 — Ejecutar y ver fallar:** el nuevo assert FALLA (hoy `test` genera `Warning: include(...test)`).
- [ ] **Step 3 — Implementar:** envolver el `include $post['embed_url']` en `if (preg_match('~^https?://~i', (string)$post['embed_url'])) { include $post['embed_url']; }`. Sustituir los comentarios que nombran la vulnerabilidad por uno neutro (p. ej. `// Vista previa del recurso enlazado por el autor del hilo.`). Quitar los comentarios "LECTURA PÚBLICA… vector RFI", "la única SQLi del reto", "ESCRITURA GATEADA… única vuln".
- [ ] **Step 4 — Ejecutar y ver pasar** (`bash tests/04_posts_rfi.sh`): PASA (requiere firewall abierto para el assert http://; si está cerrado, validar manualmente que `embed_url=test` ya no da warning).
- [ ] **Step 5 — Commit** (`fix: acotar inclusión a http(s) y neutralizar comentarios de post.php`).

### Task 3: Scrub del resto del código servido

**Files:**
- Modify: `web/src/lib/db.php`, `web/src/lib/cart.php`, `web/src/lib/layout.php`, `web/src/foro/lib/auth.php`, `web/src/foro/lib/layout.php`, `web/src/foro/index.php`, `web/src/foro/new_post.php`, `web/src/tienda/search.php`, `web/src/assets/css/shellyx.css`, `db/init/01-schema-seed.sql` (solo comentarios)
- Test: `tests/09_no_ctf_markers.sh` (crear)

**Interfaces:**
- Produces: `web/src` y el seed sin cadenas que delaten la CTF/vulns.

- [ ] **Step 1 — Test que falla:** `tests/09_no_ctf_markers.sh` hace `grep -riE` sobre `web/src` y `db/init` (excluyendo `_mockup.php`) de la lista negra: `VULNERABILID`, `deliberad`, `la única (SQLi|vuln)`, `el vector`, `salto tienda`, `cracke`, `rockyou`, `allow_url_include`, `UNION-vulnerable`, `aviso del laboratorio`, `vulnerabilidades didácticas`, `No añade ninguna vulnerabilidad`; falla si hay match.
- [ ] **Step 2 — Ejecutar y ver fallar:** FALLA (hay matches).
- [ ] **Step 3 — Implementar:** reemplazar cada comentario delator por uno neutro que describa el comportamiento como función normal, o quitarlo; en `shellyx.css` quitar cabecera "…laboratorio Docker" y el bloque "aviso del laboratorio" (y el markup del aviso si queda en `index.php`); en el seed quitar "La RFI vive…", "UNION-vulnerable", "rockyou", "el vector…". No tocar lógica.
- [ ] **Step 4 — Ejecutar y ver pasar:** `bash tests/09_no_ctf_markers.sh` PASA; además `curl` de `/`, `/tienda/`, `/foro/` siguen 200 sin `Fatal/Parse`.
- [ ] **Step 5 — Commit** (`chore: eliminar del código servido los marcadores de CTF/vulnerabilidad`).

### Task 4: `new_post.php` con selector de tablón (categoría o dispositivo)

**Files:**
- Modify: `web/src/foro/new_post.php`
- Modify: `web/src/foro/lib/layout.php` (helper de gama/productos si procede)
- Test: `tests/10_new_post_board.sh` (crear)

**Interfaces:**
- Consumes: `posts.product_id` (Task 1).
- Produces: formulario con `<select name="board">` (optgroups Categorías/Dispositivos, valores `cat:<clave>`/`dev:<id>`); el POST fija `category` **o** `product_id` tras validar. `new_post.php?dev=<id>` preselecciona.

- [ ] **Step 1 — Test que falla:** con sesión, POST con `board=dev:3` crea hilo y redirige a `post.php?id=N`; el hilo tiene `product_id=3`. Y `board=dev:999`/`board=cat:bogus` NO crea un hilo con product_id inválido (cae a categoría general por defecto, sin error 500). Assertions por web + comprobación en BD.
- [ ] **Step 2 — Ejecutar y ver fallar:** FALLA (no existe el selector ni el manejo de `board`).
- [ ] **Step 3 — Implementar:** parsear `board`: si `dev:<id>` y el id existe en `products` (consulta preparada) → `product_id=id`; si `cat:<clave>` y la clave ∈ `FORUM_CATEGORIES` → `category=clave`; en otro caso `category='general'`, `product_id=NULL`. INSERT preparado incluyendo `product_id`. Render del `<select>` con las 4 categorías y los productos (consulta preparada), preselección por `?dev=`.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: selector de tablón (categoría o dispositivo) en nueva publicación`).

### Task 5: `dispositivo.php` — subforo de un producto

**Files:**
- Create: `web/src/foro/dispositivo.php`
- Test: `tests/08_device_subforum.sh` (extender)

**Interfaces:**
- Consumes: `posts.product_id` (Task 1), selector (Task 4).
- Produces: `dispositivo.php?id=<int>` lectura pública: cabecera del producto (nombre, precio, enlace a la tienda) + listado de hilos `WHERE product_id=?` + CTA "Nueva publicación" a `new_post.php?dev=<id>`.

- [ ] **Step 1 — Test que falla (incluye Review Focus):** `dispositivo.php?id=3` (público) muestra el nombre del producto y sus hilos; `dispositivo.php?id=999` y `?id=abc` responden limpio (sin `Warning`/`Fatal`, 200 o 404 controlado); un hilo con `<script>` en el título se devuelve escapado (`&lt;script&gt;`).
- [ ] **Step 2 — Ejecutar y ver fallar** (404, página no existe).
- [ ] **Step 3 — Implementar** `dispositivo.php`: `$id=(int)$_GET['id']`; cargar producto con consulta preparada (si no existe → mensaje "dispositivo no encontrado"); listar hilos del dispositivo (preparada, autor por JOIN), todo por `e()`; reutilizar `forum_head/forum_bar/forum_breadcrumb/usercard` o el listado de la portada.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: página de subforo por dispositivo`).

### Task 6: Portada del foro estilo XDA (`index.php`) + estilos a `forum.css`

**Files:**
- Modify: `web/src/foro/index.php` (reescritura del cuerpo)
- Modify: `web/src/foro/assets/forum.css` (añadir clases de la home)
- Modify: `tests/03_forum_auth.sh` (ajustar asserts de lectura pública al nuevo marcado)
- Test: `tests/11_forum_home.sh` (crear)

**Interfaces:**
- Consumes: `product_id` + contadores (Task 1), `dispositivo.php` (Task 5).
- Produces: home de 3 columnas (Topics / categorías+dispositivos / Explora-tienda+Nuevos-posts) con contadores reales y enlaces a `dispositivo.php?id=`.

- [ ] **Step 1 — Test que falla:** `tests/11_forum_home.sh` (público) comprueba presencia de "Explora nuestra tienda", enlace a `/tienda/`, "Nuevos posts", las 4 categorías, y al menos un enlace `dispositivo.php?id=`. `tests/03` sigue exigiendo lectura pública (categorías visibles sin sesión) y que el invitado no ve "Nueva publicación".
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar:** portar el layout del mockup `_mockup.php` a `index.php` con datos y contadores reales (categorías: hilos/mensajes reales; dispositivos: `COUNT` por `product_id` reales); trasladar las clases `mk-*` a `forum.css` con nombres definitivos (p. ej. `home-grid`, `home-side`, `sub-card`, `dev-card`, `explore`, `newposts`). Caja "Explora nuestra tienda" → `/tienda/`; "Nuevos posts" = últimos hilos/respuestas.
- [ ] **Step 4 — Ejecutar y ver pasar** (`tests/11` y `tests/03`); revisión visual con screenshot.
- [ ] **Step 5 — Commit** (`feat: portada del foro estilo XDA (categorías + subforos de dispositivo)`).

### Task 7: Novedades y Miembros

**Files:**
- Create: `web/src/foro/novedades.php`, `web/src/foro/miembros.php`
- Modify: `web/src/foro/lib/layout.php` (nav: "Novedades"→`novedades.php`, "Miembros"→`miembros.php`)
- Test: `tests/12_forum_community.sh` (crear)

**Interfaces:**
- Produces: `novedades.php` (feed de actividad reciente) y `miembros.php` (directorio → `perfil.php?u=`), ambos lectura pública.

- [ ] **Step 1 — Test que falla:** `novedades.php` lista títulos de hilos recientes y autores; `miembros.php` lista los 5 usuarios y enlaza a `perfil.php?u=`. Público, sin `Warning/Fatal`.
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar** ambas páginas con consultas simples (no preparadas hace falta, pero sin entrada de usuario) y salida por `e()`; reutilizar `forum_head/forum_bar/forum_footer`, `avatar_html`, `hace`, `member_role`, `member_since`. Apuntar la nav a las páginas reales.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: páginas de novedades y miembros del foro`).

### Task 8: Perfil de usuario + enlace al propio perfil

**Files:**
- Create: `web/src/foro/perfil.php`
- Modify: `web/src/foro/lib/layout.php` (barra: `me` enlaza a `perfil.php?u=<yo>`; `usercard`/autores enlazan a `perfil.php?u=<autor>`)
- Test: `tests/12_forum_community.sh` (extender)

**Interfaces:**
- Consumes: nada nuevo.
- Produces: `perfil.php?u=<usuario>` lectura pública: tarjeta + hilos + respuestas del usuario.

- [ ] **Step 1 — Test que falla (incluye Review Focus):** `perfil.php?u=admin` muestra sus hilos y respuestas; `perfil.php?u=nope` responde limpio ("usuario no encontrado", sin `Warning`); si un título/respuesta del usuario lleva `<script>`, aparece escapado.
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar:** cargar usuario con consulta **preparada** por `username`; si no existe, mensaje limpio; listar sus hilos (incl. dispositivo) y sus respuestas con consultas preparadas, salida por `e()`. Enlazar nombres de autor y el chip de sesión a `perfil.php?u=`.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: perfil de usuario con sus hilos y respuestas`).

### Task 9: Buscador del foro + form en la barra + favicon

**Files:**
- Create: `web/src/foro/buscar.php`
- Modify: `web/src/foro/lib/layout.php` (`forum_bar`: `<input>`→`<form method="get" action="buscar.php">`; `forum_head`: `<link rel="icon">`)
- Test: `tests/13_forum_search.sh` (crear)

**Interfaces:**
- Produces: `buscar.php?q=<términos>` resultados (hilos por título/cuerpo + respuestas) con **consulta preparada**.

- [ ] **Step 1 — Test que falla (incluye Review Focus):** `buscar.php?q=Nibbler` devuelve el hilo esperado; `buscar.php?q=' OR 1=1-- -` responde 200 sin error SQL y **sin** volcar todos los hilos (no inyectable); la barra contiene `<form ... action="buscar.php"`; `forum_head` emite `rel="icon"`.
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar** `buscar.php`: `LIKE CONCAT('%', ?, '%')` con `bind_param` sobre `posts.title/body` y `replies.body` (JOIN a users/posts), salida por `e()`, resaltar término opcional (escapando antes). Cambiar la barra a `<form>` y añadir el favicon existente a `forum_head`.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: buscador del foro (preparado) + favicon`).

### Task 10: Tienda — fotos reales de producto

**Files:**
- Create: `web/src/assets/img/productos/<slug>.<ext>` (una por producto)
- Modify: `web/src/tienda/index.php` (y `tienda/search.php` si pinta fichas) — `product__media` con `<img>`+fallback
- Test: `tests/14_shop_images.sh` (crear)

**Interfaces:**
- Produces: fichas con foto real; `onerror` cae al icono actual.

- [ ] **Step 1 — Test que falla:** la home de la tienda contiene `<img` con `src` bajo `/assets/img/productos/` para los productos y conserva `onerror`; cada fichero de imagen referenciado existe y responde 200.
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar:** obtener una foto real (preferible licencia libre; registrar fuente) por producto equivalente, optimizar y guardar en `assets/img/productos/`; mapear producto→fichero (por id o slug); render `<img src=... alt=... loading="lazy" onerror="...icono fallback...">`. Si falta foto libre decente, placeholder neutro y aviso.
- [ ] **Step 4 — Ejecutar y ver pasar**; revisión visual.
- [ ] **Step 5 — Commit** (`feat: fotos reales de producto en la tienda`).

### Task 11: Tienda — secciones "Retos mensuales" y "Uso ético"

**Files:**
- Modify: `web/src/index.php` (landing: secciones `#retos` y `#etica`)
- Modify: `web/src/lib/layout.php` (`sx_footer`: enlaces)
- Test: `tests/15_shop_links.sh` (crear)

**Interfaces:**
- Produces: secciones reales `#retos` y `#etica`; footer "Retos mensuales"→`/#retos`, "Uso ético"→`/#etica`.

- [ ] **Step 1 — Test que falla:** la landing contiene `id="retos"` e `id="etica"` con texto propio; el footer enlaza `/#retos` y `/#etica` (y "Sobre Shellyx" sigue a `#empresa`).
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar:** añadir sección "Retos mensuales" (programa + CTA al foro) y "Uso ético" (uso responsable/legal) con la estética de la landing (`section`, `eyebrow`, etc.); corregir los `href` del footer.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: secciones de retos mensuales y uso ético en la landing`).

### Task 12: Tienda — sustituir GitHub por Docs

**Files:**
- Create: `web/src/docs/index.php`
- Modify: `web/src/lib/layout.php` (`sx_header` nav y `sx_footer`: GitHub → Docs)
- Test: `tests/15_shop_links.sh` (extender)

**Interfaces:**
- Produces: `/docs/` placeholder; nav y footer enlazan a Docs; sin enlaces a github.com.

- [ ] **Step 1 — Test que falla:** nav y footer **no** contienen `github.com`; contienen enlace a `/docs/`; `GET /docs/` responde 200 con "Documentación".
- [ ] **Step 2 — Ejecutar y ver fallar.**
- [ ] **Step 3 — Implementar:** `docs/index.php` con `sx_header('Docs','docs')`/`sx_footer` y placeholder "Documentación — próximamente" + esqueleto de secciones; quitar el `<a github>` de nav y footer y poner "Docs"→`/docs/`.
- [ ] **Step 4 — Ejecutar y ver pasar.**
- [ ] **Step 5 — Commit** (`feat: página Docs y retirada de GitHub en la tienda`).

### Task 13: Cierre — limpieza y verificación completa

**Files:**
- Delete: `web/src/foro/_mockup.php`
- Modify: `tests/run_all.sh` (añadir los tests nuevos)
- Modify: `README.md` (reflejar páginas nuevas del foro y Docs; opcional)

- [ ] **Step 1 — Añadir** los tests 08–15 a `run_all.sh` en orden.
- [ ] **Step 2 — Borrar** `_mockup.php` y comprobar que ninguna página lo referencia.
- [ ] **Step 3 — Re-seed limpio** y ejecutar toda la suite; capturar verde (04 depende del firewall).
- [ ] **Step 4 — Revisión visual** final (foro home, dispositivo, perfil, tienda con fotos, docs) por screenshot.
- [ ] **Step 5 — Commit** (`chore: wiring de tests y limpieza del mockup`) y **push** a `branch-lino`; abrir PR a `main`.

## Autorrevisión (hecha)

- **Cobertura del spec:** A→Task 6; B→Tasks 1,4,5(+6); C→Tasks 7,8,9; D→Tasks 10,11,12; E→Tasks 2,3; F→Task 2. Entrega→Task 13.
- **Review Focus:** buscador no inyectable (Task 9), XSS escapado (Tasks 5,8), board manipulado (Task 4), id inválido (Task 5), embed no-http (Task 2). Todos con test en su task.
- **Consistencia de nombres:** `product_id`, `board=cat:|dev:`, `dispositivo.php?id=`, `perfil.php?u=`, `buscar.php?q=`, `novedades.php`, `miembros.php`, `/docs/` usados igual en todas las tasks.
- **Proporción:** plan en firmas/aserciones, sin cuerpos de función salvo el guard de inclusión; más corto que el código que genera.
