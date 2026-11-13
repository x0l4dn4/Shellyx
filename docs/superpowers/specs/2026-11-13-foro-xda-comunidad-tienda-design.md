# Diseño — Foro estilo XDA + subforos por producto, comunidad y pulido de tienda

Fecha: 2026-11-13 · Rama de entrega: `branch-lino` (ya al nivel de `main`) → PR a `main`.

## Intención y por qué

El proyecto (tienda + foro "Shellyx", un laboratorio web deliberadamente vulnerable)
ya está en `main`. El equipo pide una pasada de pulido y de funcionalidad para que
parezca un producto real: rehacer la portada del foro al estilo de XDA Forums, añadir
un subforo por cada producto de la tienda, completar las páginas de comunidad que hoy
son placeholders, arreglar enlaces/estética de la tienda, y **eliminar del código
servido cualquier rastro que delate que esto es una CTF o que señale sus
vulnerabilidades**. El objetivo de fondo: que el laboratorio sea más creíble y
navegable, sin alterar las vulnerabilidades que lo hacen útil.

## Objetivos

1. **Portada del foro estilo XDA** (layout de 3 columnas, validado en mockup):
   izquierda "Topics" (categorías + dispositivos), centro (categorías + subforos de
   dispositivo), derecha ("Explora nuestra tienda" + "Nuevos posts").
2. **Subforo por producto**: cada producto de la tienda tiene su tablón donde abrir y
   responder hilos.
3. **Páginas de comunidad** que hoy no existen: Novedades, Miembros, Perfil de usuario
   (con sus hilos y respuestas), acceso a tu propio perfil, y **buscador del foro**
   funcional. Favicon en el foro.
4. **Tienda**: fotos reales de producto en vez de iconos; secciones reales para "Retos
   mensuales" y "Uso ético"; sustituir GitHub por una página **Docs** (placeholder).
5. **Scrub de CTF**: ningún comentario ni texto servido revela las vulnerabilidades ni
   menciona "CTF/reto/laboratorio/vulnerabilidad deliberada".
6. **Arreglar el warning al crear post** sin romper la vulnerabilidad.

## No-objetivos

- No rediseñar la identidad visual: todo se construye con el sistema que ya existe
  (`foro/assets/forum.css` + helpers de `foro/lib/layout.php` en el foro;
  `assets/css/shellyx.css` + `sx_header/sx_footer` en la tienda).
- No tocar el carrito/checkout de la tienda (funciona).
- No añadir documentación real a Docs todavía (es un placeholder).

## Anti-objetivos (harían fracasar el trabajo aunque "funcione")

- **Introducir una vulnerabilidad nueva no planeada**: las páginas nuevas del foro
  (perfil, miembros, novedades, buscador, selector de tablón) deben usar **sentencias
  preparadas** y escapar la salida con `e()`. La única inyección del laboratorio es la
  del buscador de la **tienda**; el foro no debe duplicarla.
- **Romper la cadena existente**: tras los cambios siguen vivas, sin cambios de
  comportamiento, la inyección del buscador de la tienda y la inclusión remota del foro
  (ahora acotada a URLs `http(s)`), y los hashes MD5 débiles.
- **Que el scrub cambie comportamiento**: solo se tocan comentarios/textos, nunca la
  lógica vulnerable.

## Restricciones

- PHP 7.4 + MariaDB, servido por el contenedor existente (bind-mount de `web/src`, así
  que los cambios de PHP se ven en vivo; los de esquema requieren re-seed
  `docker compose down -v && up -d --build`).
- El firewall host→contenedor sigue bloqueando el callback de la inclusión remota en
  los tests; es ambiental y está documentado.
- Entrega en `branch-lino` y PR a `main` (como el PR #1). Nada de commits directos a `main`.

## Enfoques considerados

**Modelo de datos de los subforos de dispositivo** (decisión principal):
- **(elegido) Columna `product_id INT NULL` en `posts`.** Un hilo pertenece **o** a una
  categoría general (`category`, como hoy) **o** a un dispositivo (`product_id`). Índice
  en `product_id`. Mínimo cambio, reutiliza toda la lógica de hilos/respuestas/contadores.
- (descartado) Tratar cada producto como "categoría" dinámica (`category='dev:3'`):
  choca con el mapa fijo `FORUM_CATEGORIES` y enreda las 4 categorías generales.
- (descartado) Tabla aparte `device_threads`: duplica la lógica de posts/replies y
  complica contadores y "nuevos posts".

**Fotos de producto**:
- **(elegido) Fotos reales** de dispositivos equivalentes del mundo real, **de internet,
  preferentemente con licencia libre** (CC0/Unsplash/Pexels/Wikimedia), una por producto,
  en `web/src/assets/img/productos/<slug>.<ext>`. La ficha renderiza `<img>` con
  `onerror` que cae al icono actual. Se registra la fuente/licencia de cada una. Nada
  dibujado ni logos (indicación del autor).
- (descartado) Ilustraciones/placeholders vectoriales: el autor pidió fotos reales.

**"Retos mensuales" / "Uso ético"**:
- **(elegido) Secciones reales en la landing** con contenido propio, separando el anchor
  duplicado: "Retos mensuales" → `#retos`, "Uso ético" → `#etica` (hoy ambos enlaces
  caen en secciones que no tratan el tema y "Sobre Shellyx"+"Uso ético" apuntan los dos
  a `#empresa`). Más ligero y encaja con la estructura actual.
- (descartado) Páginas dedicadas `/retos`, `/etica`: más peso para contenido breve.

## Diseño por área

### A. Portada del foro (XDA, 3 columnas) — `foro/index.php`
Layout validado en el mockup (`_mockup.php`, desechable):
- **Izquierda "Topics"**: lista de las 4 categorías (con punto de color) + lista de
  **Dispositivos** (enlaces a cada subforo). Navegación rápida.
- **Centro**: cabecera "Foros Shellyx" con enlace a "Nuevos posts"; sección **Comunidad**
  (las 4 categorías como tarjetas: icono + descripción + contadores reales); debajo,
  **subforos de dispositivo** agrupados por gama (Entrada / Avanzada / Módulos / Packs),
  cada tarjeta con foto + nombre + precio + contadores + borde de color por gama,
  enlazando a `dispositivo.php?id=<id>`.
- **Derecha**: caja **"Explora nuestra tienda"** (CTA a `/tienda/` + estadísticas) y
  panel **"Nuevos posts"** (avatar + título + "último: usuario · hace…").
- Los estilos `mk-*` del mockup se trasladan a `forum.css` con nombres definitivos.
- Responsive: a una columna en móvil.

### B. Subforos por dispositivo
- **Esquema** (`db/init/01-schema-seed.sql`): `ALTER`/definición con `product_id INT NULL`
  en `posts` + `INDEX(product_id)`. Hilos de dispositivo llevan `product_id` y
  `category` queda en su `DEFAULT`.
- **Seed**: sembrar 1–2 hilos por producto (contenido on-theme) para que los subforos no
  salgan vacíos en la demo; respuestas opcionales. Los contadores de la portada pasan a
  ser **reales**.
- **`new_post.php`**: selector de tablón `<select name="board">` con dos `optgroup`
  ("Categorías" y "Dispositivos"). El valor codifica `cat:<clave>` o `dev:<id>` y se
  valida en servidor contra categorías conocidas y productos (consulta preparada) antes
  de fijar `category` o `product_id`. Preselección si se llega desde un dispositivo
  (`new_post.php?dev=<id>`).
- **`dispositivo.php?id=<id>`** (nuevo, lectura pública): cabecera del dispositivo
  (nombre, precio, enlace "Ver en la tienda"), listado de hilos `WHERE product_id = ?`
  (preparada), y botón "Nueva publicación" preseleccionando ese dispositivo.
- **`post.php`**: el breadcrumb/contexto refleja el dispositivo si el hilo tiene
  `product_id`.

### C. Páginas de comunidad + favicon
- **`novedades.php`**: feed cronológico completo de actividad (hilos nuevos + últimas
  respuestas), lectura pública. La barra "Novedades" apunta aquí.
- **`miembros.php`**: directorio de miembros (avatar, rol, "desde", nº de mensajes),
  cada uno enlaza a su perfil.
- **`perfil.php?u=<usuario>`**: tarjeta del usuario + sus hilos + sus respuestas
  ("sus comentarios"). Usuario validado con consulta preparada; 404 limpio si no existe.
  Lectura pública. El nombre del usuario conectado en la barra enlaza a su propio perfil.
- **`buscar.php?q=<términos>`**: búsqueda por título/cuerpo de hilos y cuerpo de
  respuestas con **sentencia preparada** y `LIKE` parametrizado; página de resultados
  tipo listado. El `<input>` de la barra pasa a `<form method="get" action="buscar.php">`.
- **Favicon**: `forum_head()` añade `<link rel="icon" href="/assets/img/favicon.svg">`
  (se reutiliza el favicon existente; no se crea nada nuevo).
- Enlaces de la barra (brand, nav) quedan coherentes con las páginas reales.

### D. Tienda
- **Imágenes** (`tienda/index.php` y resultados de `search.php`): `product__media`
  renderiza `<img src="/assets/img/productos/<slug>...">` con `onerror` que cae al icono
  actual (sin romper si falta el fichero). Mapa producto→foto documentado con su fuente.
- **Retos mensuales / Uso ético** (`index.php` landing + `lib/layout.php` footer):
  sección `#retos` (programa de retos mensuales, CTA al foro) y sección `#etica`
  (uso responsable/legal). El footer enlaza "Retos mensuales"→`/#retos` y
  "Uso ético"→`/#etica`; "Sobre Shellyx" sigue a `#empresa`.
- **GitHub → Docs**: quitar el enlace de GitHub de `sx_header` (nav) y `sx_footer`
  (footer). Crear `web/src/docs/index.php` (usa `sx_header/sx_footer`) con placeholder
  "Documentación — próximamente" y un esqueleto de secciones. Nav y footer enlazan a
  `/docs/`.

### E. Scrub de marcadores de CTF (sin cambiar comportamiento)
Sustituir los comentarios que anuncian vulnerabilidades por comentarios neutros que
describan el comportamiento como una función normal, o quitarlos. Afecta al código
**servido** y al seed:
- `foro/post.php` (inclusión "VULNERABILIDAD DELIBERADA… RFI", "la única SQLi del reto",
  "LECTURA PÚBLICA… el vector RFI…", "ESCRITURA GATEADA… la única vuln…").
- `foro/new_post.php` ("el vector que post.php incluye → RFI… preserva la cadena del reto").
- `foro/lib/auth.php` ("la única SQLi del reto… se saltaría el crackeo").
- `foro/lib/layout.php` ("el foro NO es vulnerable a XSS; la única vuln… RFI").
- `foro/index.php` ("la RFI sigue requiriendo credenciales crackeadas").
- `lib/db.php` ("habilita el salto tienda → foro vía UNION en la SQLi", "sigue siendo
  inyectable").
- `lib/cart.php` ("CTF. No añade ninguna vulnerabilidad…").
- `lib/layout.php` ("vulnerabilidades didácticas del laboratorio (SQLi…, RFI…)").
- `tienda/search.php` (comentarios que explican la inyección).
- `assets/css/shellyx.css` (cabecera "…dentro del laboratorio Docker" y bloque
  "aviso del laboratorio") + eliminar markup/CSS del aviso de laboratorio si queda.
- `db/init/01-schema-seed.sql` (comentarios "La RFI vive en posts.embed_url",
  "el buscador sigue siendo UNION-vulnerable", "contraseñas del top 1000 de rockyou",
  "ningún RFI accidental… el vector…").
- Contenido sembrado "in-world" (p. ej. normas del foro, posts) se conserva como flavor;
  solo se suaviza lo que suene a aviso de laboratorio si procede.
- **Fuera de alcance** (docs internos del autor/equipo, no servidos): `README.md`, los
  tests de `tests/`, y estos `docs/superpowers/*`. Se puede ampliar si lo pides.

### F. Arreglo del warning al crear post (inclusión remota)
`foro/post.php`: incluir solo si `embed_url` empieza por `http://`/`https://`
(`preg_match('~^https?://~i', …)`); si no, no hay vista previa y no se emite warning. La
inclusión remota sigue funcionando igual para un atacante; un valor no-URL (p. ej.
`test`) deja de ensuciar la página. (Se pierden LFI y wrappers como vías alternativas,
aceptado por el autor.)

## Modelo de datos (cambio de esquema)

```
posts: + product_id INT NULL, + INDEX(product_id)
```
Un hilo es de categoría (hoy) o de dispositivo (`product_id` no nulo). `replies` no
cambia (cuelgan del `post_id`). Requiere re-seed para aplicarse.

## Seguridad (posture tras los cambios)

- **Se mantienen** (comportamiento idéntico): inyección SQL del buscador de la tienda;
  inclusión remota del foro vía `embed_url` (acotada a `http(s)`); hashes MD5 débiles.
- **No se añade nada**: todas las páginas/consultas nuevas del foro van con sentencias
  preparadas y salida escapada con `e()`. El selector de tablón valida contra
  categorías/productos conocidos.
- El scrub es puramente textual.

## Pruebas

Ampliar la suite `tests/` (no servida): búsqueda del foro no inyectable (una comilla no
rompe ni filtra de más), perfil/miembros/novedades escapan la salida, subforo de
dispositivo lista y permite publicar con sesión, y la inclusión remota sigue ejecutando
PHP remoto por `http://` (test 04 existente, dependiente del firewall). Verificación con
re-seed limpio.

## Lo que queda al builder

- Redacción final de los textos de las secciones "Retos mensuales" y "Uso ético" y del
  placeholder de Docs.
- Selección concreta de cada foto de producto y su fuente/licencia; si alguna no tiene
  foto libre decente, placeholder neutro y aviso.
- Nombres/afinado final de las clases CSS nuevas (reutilizando tokens de `forum.css` y
  `shellyx.css`).
- Cantidad exacta de hilos/respuestas sembrados por dispositivo.

## Entrega

Trabajo en `branch-lino` (ya fast-forward a `main`), commits temáticos, push y PR a
`main`. Se elimina el `_mockup.php` antes de cerrar.
