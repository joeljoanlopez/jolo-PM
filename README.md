# jolo-PM

Tablero kanban del progreso de mis proyectos, **leído de las casillas de sus
markdowns**. No es un gestor de tareas: no tiene tareas propias ni escribe en
los repos. Los markdowns son la fuente de verdad; jolo-PM solo los interpreta.

```
docs/plan/E03-github.md                       Por hacer   En curso   En revisión   Hecho
- [x] Puerto SourceHost            ──────►                            ┌──────────┐
- [x] Adaptador GitHub                                                │ E03  5/5 │
- [x] Caché de blobs                                                  └──────────┘
```

## Cómo decide la columna

Cada fichero seguido es una tarjeta. Su columna sale de sus casillas:

| Columna | Cuándo |
| --- | --- |
| **Por hacer** | ninguna casilla marcada en la rama principal |
| **En curso** | alguna marcada, no todas |
| **En revisión** | una PR abierta contra la rama principal marca casillas del fichero |
| **Hecho** | todas marcadas en la rama principal |

El flujo de trabajo no cambia: marcas la casilla en tu rama junto con el código,
abres la PR → la tarjeta pasa a *En revisión*; haces merge → *En curso* o *Hecho*.

Vale cualquier casilla de lista (`- [ ]`, `* [x]`, `1. [ ]`); las de bloques de
código se ignoran. Las tareas se agrupan por el encabezado bajo el que están.

## Puesta en marcha

```sh
cp .env.example .env      # y rellena GITHUB_TOKEN y APP_KEY
echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env   # una vez; no la cambies
docker compose up -d --build
```

La web queda en `http://127.0.0.1:8095`, solo en local a propósito: para
abrirla desde otros dispositivos, ponle delante un proxy inverso. El
`compose.yaml` trae las labels de un generador de vhosts de nginx
(`nginx.autoconf`, `nginx.domain`); si usas otro proxy, quítalas o cámbialas
por las suyas.

`APP_KEY` es obligatoria: cifra las cookies de sesión y el secreto del 2FA. Si
la cambias, el 2FA deja de funcionar y hay que volver a crear la cuenta (ver
[Si pierdes el acceso](#si-pierdes-el-acceso)).

La base de datos SQLite va a `./data`.

### Acceso: contraseña y 2FA

La web tiene **un solo usuario**, sin nombre: se entra con contraseña y un
código de 6 dígitos de una app de autenticación (Aegis, 1Password, Google
Authenticator…). Sin sesión, la API solo responde `/api/health` y `/api/auth/*`.

**Primera vez.** Al arrancar sin cuenta, el backend escribe en sus logs un
código de configuración de un solo uso:

```sh
docker compose logs backend      # busca "código de configuración"
```

Abre la web y sigue el asistente: el código de los logs → la contraseña (mínimo
12 caracteres) → escanear el QR con la app y escribir un código → guardar los
**10 códigos de recuperación**, que no se vuelven a mostrar. Sin acceso al
servidor nadie puede crear la cuenta antes que tú. Cada arranque sin cuenta
genera un código nuevo.

**Día a día.** Contraseña y código. La sesión dura 7 días de inactividad
(`SESSION_LIFETIME`, en minutos). Desde el escudo de la cabecera se ven los
códigos de recuperación que quedan y se pueden generar otros; el botón de al
lado cierra la sesión. Tras 5 intentos fallidos en un minuto desde la misma IP
(contraseña, código o código de configuración), la API responde 429 hasta que
pasa el minuto. Cada intento queda registrado con IP y hora en la tabla
`login_attempts`.

**HTTPS.** La cookie de sesión es `Secure`: el navegador solo la manda por
HTTPS o a `localhost`. Si tu proxy sirve la web por HTTP, el login no se
mantendrá; sírvela por HTTPS o, solo en una red de confianza, pon
`SESSION_SECURE_COOKIE=false` en el `.env`. Para que el límite de intentos y el
registro vean la IP real, el proxy debe mandar `X-Forwarded-For`.

#### Si pierdes el acceso

- **Sin el móvil:** en la pantalla del código, *Usar un código de recuperación*.
  Cada uno vale una vez; luego genera otros desde el escudo de la cabecera.
- **Sin contraseña, sin móvil y sin códigos:** borra la cuenta desde el servidor.
  Los proyectos no se tocan; la web vuelve al asistente y el comando muestra el
  código de configuración nuevo (también sale en los logs al reiniciar):

  ```sh
  docker compose exec backend php artisan auth:reset
  ```

### Token de GitHub

Solo necesita **leer**, y conviene que vea únicamente los repos que quieres
seguir. Opciones:

- **Fine-grained token** (recomendado): *Settings → Developer settings →
  Fine-grained tokens*. Acceso a los repos que quieras seguir y permisos de solo
  lectura en **Contents**, **Pull requests** y **Metadata**. Cada token pertenece
  a un único propietario (tu usuario o una organización), así que solo ve los
  repos de ese propietario.
- **Classic token** con scope `repo`: ve los repos de todos los propietarios a la
  vez, pero el scope da también permiso de escritura.

### Conectar proyectos

Desde la web: **Añadir proyecto** → elige el repo, los globs de los ficheros a
leer (uno por línea) y, si quieres, una rama distinta de la principal.

| Glob | Casa con |
| --- | --- |
| `docs/plan/*.md` | los `.md` de esa carpeta, sin subcarpetas |
| `docs/**/*.md` | los `.md` de `docs/` y todas sus subcarpetas |
| `TODO.md` | ese fichero exacto en la raíz |

El worker sincroniza todos los proyectos cada 5 minutos (`SYNC_CRON`); el botón
**Sincronizar** lo hace al momento.

La web está en castellano y en inglés: el selector **ES | EN** de la cabecera
cambia el idioma y se recuerda en el navegador.

## Desarrollo

Arquitectura hexagonal en backend y frontend; ver
[`docs/architecture.md`](docs/architecture.md). El plan del propio proyecto está
en [`docs/plan/`](docs/plan/README.md) y se ve en el tablero de jolo-PM.

```sh
# Backend: Laravel 13 / PHP 8.4. Sin PHP en el host, con Docker:
cd backend
docker run --rm -u $(id -u):$(id -g) -v $PWD:/app -w /app composer:2 \
  sh -c 'composer install -q && vendor/bin/phpunit'

# Frontend: React + TypeScript + Vite. Con el stack levantado, /api se
# redirige al puerto 8095.
cd frontend
npm install
npm run dev          # http://localhost:5173
npm test && npm run typecheck
```

| Comando (en el contenedor `worker`) | Qué hace |
| --- | --- |
| `php artisan projects:sync [id]` | Sincroniza todos los proyectos, o uno |
| `php artisan blobs:prune` | Purga la caché de ficheros sin usar (`BLOB_TTL_DAYS`) |
| `php artisan schedule:list` | Muestra cuándo toca cada tarea |
| `php artisan auth:reset` | Borra la cuenta para volver a crearla (en el contenedor `backend`; no toca los proyectos) |
