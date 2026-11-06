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

Cada fichero seguido es una tarjeta (o cada sección `##`, si el proyecto agrupa
por sección). Su columna sale de sus casillas:

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
cp .env.example .env      # y rellena GITHUB_TOKEN
mkdir -p data             # con tu usuario: si la crea Docker, es de root
docker compose up -d --build
```

La web queda en `http://127.0.0.1:8095`, solo en local a propósito: para
abrirla desde otros dispositivos, ponle delante un proxy inverso. El
`compose.yaml` trae las labels de un generador de vhosts de nginx
(`nginx.autoconf`, `nginx.domain`); si usas otro proxy, quítalas o cámbialas
por las suyas.

jolo-PM **no tiene login**: cualquiera que llegue a la web ve los repos a los
que tiene acceso el token. Limita el acceso desde el proxy (red local, VPN…).

La base de datos SQLite va a `./data`.

### Token de GitHub

Solo necesita **leer**, y conviene que vea únicamente los repos que quieres
seguir (ver la nota de arriba sobre el login). Opciones:

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

**Una tarjeta por** fichero (por defecto) o por sección: con *Sección* cada
encabezado `##` con casillas es una tarjeta (`Plan/Sección`) que enlaza a su
ancla en GitHub, y
las casillas anteriores al primer `##` van a una tarjeta con el título del
fichero. Útil para planes que lo meten todo en un único `PLAN.md`. Renombrar una
sección cuenta como tarjeta nueva.

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
