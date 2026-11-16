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
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env   # genera APP_KEY
mkdir -p data             # con tu usuario: si la crea Docker, es de root
docker compose up -d --build
```

La web queda en `http://127.0.0.1:8095`, solo en local a propósito: para
abrirla desde otros dispositivos, ponle delante un proxy inverso. El
`compose.yaml` trae las labels de un generador de vhosts de nginx
(`nginx.autoconf`, `nginx.domain`); si usas otro proxy, quítalas o cámbialas
por las suyas.

`APP_KEY` cifra las cookies, la sesión y el secreto del 2FA: genérala una vez
y no la cambies. Si cambia, el 2FA deja de funcionar y hay que rehacer la
cuenta con `auth:reset` (ver abajo). Si vienes de una versión sin login, tu
`.env` no tiene la línea: añádela con
`echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env`. Sin ella el compose
no arranca.

### Login con 2FA

La web tiene **un solo usuario**, con contraseña y segundo factor (TOTP)
obligatorio. La primera vez que la abres sale el **asistente de configuración
inicial**:

1. **Código de configuración**: el backend lo escribe en sus logs al arrancar
   mientras no hay cuenta. Así nadie de la red puede quedarse la cuenta antes
   que tú.

   ```sh
   docker compose logs backend | grep -A2 "código de configuración"
   ```
2. **Contraseña**: mínimo 12 caracteres.
3. **2FA**: escanea el QR con tu app (Aegis, 1Password, Google Authenticator…)
   y escribe el código que te da.
4. **Códigos de recuperación**: 10 códigos de un solo uso para entrar si
   pierdes el móvil. Guárdalos: no se vuelven a enseñar. Con la sesión
   iniciada puedes generar otros desde el icono de la llave de la cabecera
   (pide un código de la app).

La sesión dura mientras la uses y caduca tras `SESSION_LIFETIME` minutos sin
usarla (7 días por defecto). Tras 5 intentos fallidos en un minuto (contraseña,
código o código de configuración), esa IP tiene que esperar.

**Si pierdes el acceso** (contraseña, móvil y códigos), o quieres cambiar la
contraseña o el móvil del 2FA:

```sh
docker compose exec backend php artisan auth:reset
```

Borra la cuenta (los proyectos se quedan), cierra todas las sesiones y
escribe un código de configuración nuevo: la web vuelve al asistente.

#### El proxy y la IP real

El límite de intentos va por IP, así que el backend necesita la IP real del
cliente y no la del proxy. Tu proxy tiene que mandar `X-Forwarded-For` (en
nginx: `proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;`, que los
generadores de vhosts ya ponen). Con `TRUSTED_PROXIES=REMOTE_ADDR` (el valor
por defecto) solo se cree esa cabecera cuando viene de quien conecta a la web,
que es tu proxy, porque la web solo escucha en `127.0.0.1`. Si tu proxy no
manda la cabecera, todas las peticiones cuentan como una misma IP y cualquiera
podría bloquear el login. No uses `*`: en Laravel significa fiarse de
cualquiera, y el cliente podría inventarse la IP.

La cookie de sesión solo viaja por HTTPS (`SESSION_SECURE_COOKIE=true`): entra
por el proxy con HTTPS. `http://localhost` también vale, los navegadores lo
tratan como seguro.

La base de datos SQLite va a `./data`.

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

### Despliegue automático (opcional)

El servidor puede actualizarse solo cuando se hace merge a `main`, sin abrir
puertos ni instalar un runner de GitHub: un timer de systemd **de usuario**
lanza `deploy/auto-deploy.sh` cada 5 minutos. Cuando hay un commit nuevo en
`origin/main` con el CI en verde, el script hace fast-forward y
`docker compose up -d --build`. Si el CI está pendiente, lo intenta en la
siguiente vuelta, y si está en rojo, no despliega. Tampoco toca nada si la
copia está en otra rama, tiene cambios sin commitear o tiene commits que no
están en `origin/main`: lo deja registrado y sale.

Viene desactivado. Para activarlo, en el servidor:

```sh
# 1. En .env
AUTO_DEPLOY=true

# 2. Instala y arranca el timer (sin sudo; AUTO_DEPLOY_INTERVAL=10min para cambiar el intervalo)
deploy/install-auto-deploy.sh

# 3. Para que el timer corra sin una sesión abierta
loginctl enable-linger "$USER"
```

Requisitos: tu usuario puede usar `docker` sin sudo, y está instalado `gh`
autenticado o, si no, `curl` y `jq` (el repo es público y la API se consulta
sin token).

```sh
systemctl --user list-timers jolo-pm-deploy.timer   # próxima ejecución
journalctl --user -u jolo-pm-deploy.service -f      # logs
systemctl --user start jolo-pm-deploy.service       # comprobar ahora
deploy/install-auto-deploy.sh uninstall             # quitarlo
```

Con `AUTO_DEPLOY` distinto de `true`, el timer sigue corriendo pero no hace
nada. Si el `compose` falla después del merge, se reintenta en cada vuelta
hasta que sale bien.

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
| `php artisan auth:reset` | Borra la cuenta (no los proyectos) y vuelve al asistente (en el contenedor `backend`) |
| `php artisan auth:prune-logins` | Purga el registro de inicios de sesión de más de 90 días (lo hace el worker cada día) |
| `php artisan schedule:list` | Muestra cuándo toca cada tarea |
