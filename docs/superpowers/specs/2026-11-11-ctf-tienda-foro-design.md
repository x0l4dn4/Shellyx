# CTF — Tienda + Foro (SQLi → crackeo → RFI → RCE)

**Diseño del entorno · 2026-11-11**

## 1. Objetivo y contexto

Laboratorio CTF **deliberadamente vulnerable**, aislado en Docker, para una presentación de máster. Un único `docker compose up` levanta dos contenedores (web + BD). El jugador parte sin credenciales y debe encadenar vulnerabilidades hasta lograr ejecución remota de comandos (RCE) en el contenedor web.

**Sin flags**: la prueba de resolución es la webshell funcionando.

> Este entorno **no está endurecido a propósito**: las vulnerabilidades son el contenido didáctico. Debe ejecutarse únicamente en un entorno controlado/local, nunca expuesto a una red no confiable.

## 2. Cadena de ataque (camino previsto)

1. **SQLi** en el buscador de la tienda (UNION-based, visible).
2. Volcado de la tabla `users` del foro (BD compartida): `username` + `password_md5`.
3. **Crackeo offline** de los hashes MD5 (contraseñas del top 1000 de rockyou) con john/hashcat.
4. **Login en el foro** con credenciales crackeadas (registro deshabilitado → no hay atajo).
5. **Creación de un post** con "enlace externo a incrustar" → el servidor hace `include()` del recurso remoto → **RFI**.
6. El jugador aloja una webshell en su máquina; el RFI la incluye → **RCE** en el contenedor web (`www-data`).

## 3. Arquitectura y topología

Dos contenedores, un único `docker-compose.yml`:

- **web** — Apache + PHP. Sirve `/tienda` y `/foro`. Publica el `80` del contenedor en el host (p. ej. `8080:80`). Única puerta de entrada del jugador.
- **db** — MariaDB. **Sin publicar puerto al host**: solo accesible desde `web` por la red interna de compose. El único camino a la BD es la SQLi.

**Redes:**

- Red interna de compose entre `web` y `db`.
- **Salida para el RFI**: `web` alcanza la máquina del jugador por la pasarela de Docker. El jugador aloja la webshell en su host (`python3 -m http.server`, escuchando en `0.0.0.0`) y usa su IP real (`ip a`): `http://<ip>:<puerto>/payload.php`.
- **Seguro adicional**: `extra_hosts: ["attacker:host-gateway"]` en `web`, para disponer siempre de `http://attacker:<puerto>/...` aunque la IP del host cambie o no haya red.

**Gotchas documentados:**

- El payload server debe escuchar en `0.0.0.0`, no en `127.0.0.1`.
- No reutilizar el puerto publicado por la web (8080) para el payload; usar otro (p. ej. 8000).
- Un firewall restrictivo del host (`ufw`/`firewalld`) puede bloquear el tráfico contenedor→host.

## 4. Contenedor web (Apache/PHP)

- Imagen base: **`php:7.4-apache`** (allow_url_include disponible y fiable; encaja con el tema "foro legacy"). *Verificar en build que `allow_url_include` sigue operativo en la versión elegida.*
- `php.ini` del reto (mala configuración **deliberada**):
  - `allow_url_fopen = On`
  - `allow_url_include = On`
- DocumentRoot sirviendo las dos apps bajo rutas `/tienda` y `/foro` (sin tocar `/etc/hosts`).
- Interfaz mínima tipo carcasa: HTML plano funcional, **sin CSS** ni JS de presentación (solo el marcado imprescindible).
- Credenciales de conexión a la BD en la app: **un único usuario de BD** con acceso a todo el esquema → habilita el salto tienda→foro vía UNION.

## 5. Contenedor db (MariaDB)

- Imagen oficial de MariaDB.
- Script de init que crea el esquema y siembra los datos al arrancar.
- Un único usuario de aplicación con permisos sobre todas las tablas del esquema (no se expone al host).

## 6. Esquema de BD (compartido)

Una sola base de datos. Tablas:

- `products` — `id`, `name`, `price`, `description`. (Catálogo vacío de momento; opcionalmente 1-2 filas demo para validar buscador/SQLi en desarrollo, eliminables.)
- `users` — `id`, `username`, `password_md5`. (Loot del foro.)
- `posts` — `id`, `user_id`, `title`, `body`, `embed_url`, `created_at`.

## 7. Vulnerabilidad 1 — SQLi (tienda)

- **Endpoint**: buscador con filtros de la tienda.
- Query construida por **concatenación de strings** sobre `products`, p. ej.:
  ```sql
  SELECT id, name, price, description FROM products WHERE name LIKE '%<q>%'
  ```
- **UNION-based y visible** (4 columnas). El atacante inyecta, p. ej.:
  ```sql
  ' UNION SELECT id, username, password_md5, NULL FROM users -- -
  ```
  y ve `username`/`password_md5` en los campos visibles del listado.
- Explotable **a mano** y con `sqlmap`.

## 8. Vulnerabilidad 2 — RFI (foro)

- **Login** contra `users` comparando MD5 (`password_md5 = MD5(<input>)`).
- **Registro deshabilitado**: el botón "Registrarse" muestra el mensaje *"Acceso solo por invitación"*.
- **Crear post**: formulario con `title`, `body`, `embed_url`.
- Al **renderizar el post**, el servidor hace `include($embed_url)` para "mostrar la vista previa del recurso" → inclusión remota con `allow_url_include=On`.
- Cualquier usuario autenticado puede postear → crackear **cualquier** usuario basta para llegar al RFI.

## 9. Datos sembrados (editables)

Usuarios del foro (todas las contraseñas en el **top 1000 de rockyou**, almacenadas como **MD5**). Propuesta por defecto — cámbialos a tu gusto:

| username    | contraseña (clara, solo para construir el hash) |
|-------------|-------------------------------------------------|
| `admin`     | `letmein`                                        |
| `moderador` | `password`                                       |
| `jsanchez`  | `iloveyou`                                       |
| `mlopez`    | `qwerty`                                          |
| `agarcia`   | `sunshine`                                        |

- Opcional: 1 post de ejemplo para que el foro no arranque vacío.
- Productos: **vacío** (pendiente de tu personalización posterior).

## 10. Prueba de resolución

Sin flags. "Resuelto" = ejecución de comandos en el contenedor web vía la webshell incluida por RFI (`whoami` → `www-data`). La webshell la aporta el jugador.

## 11. Entrega

- `docker-compose.yml` + carpetas de apps + init SQL + `php.ini`.
- Arranque: `docker compose up` (o `-d`).
- Acceso del jugador: `http://localhost:8080/tienda` y `http://localhost:8080/foro`.

## 12. Fuera de alcance / futuro

- Productos reales y estética de la tienda (personalización posterior).
- Endurecimiento / restricción de salida (egress lockdown) opcional del contenedor.
- No hay flags ni sistema de puntuación.
