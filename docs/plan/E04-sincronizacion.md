# E04 — Sincronización y persistencia

## Objetivo
Calcular el tablero de cada proyecto a partir de GitHub y guardarlo, para que
la interfaz sea instantánea y no dependa de que GitHub responda en ese momento.

## Alcance
- **Proyecto** = repo + rama (opcional, por defecto la principal del repo) +
  lista de globs + nombre visible opcional. Se guarda en SQLite.
- **Sincronizar** un proyecto:
  1. Ficheros de la rama que casan con los globs.
  2. Tareas de cada fichero.
  3. PRs abiertas contra esa rama; para cada fichero seguido que tocan, las
     tareas de la versión de la PR.
  4. Una tarea está **en revisión** si está sin marcar en la rama y marcada en
     alguna PR abierta.
- **Columna de la tarjeta** (un fichero = una tarjeta; los ficheros sin casillas
  no salen): Hecho si todas marcadas; si no, En revisión si alguna está en
  revisión; si no, En curso si alguna marcada; si no, Por hacer.
- El tablero calculado se guarda como snapshot JSON. Si la sincronización falla,
  se conserva el último snapshot bueno y se guarda el error aparte.
- **Worker**: contenedor aparte con el scheduler de Laravel
  (`php artisan schedule:work`) que lanza `projects:sync` según `SYNC_CRON`
  (cada 5 minutos por defecto) y `blobs:prune` una vez al día.

## Criterios de aceptación
- Marcar una casilla en una rama y abrir PR mueve la tarjeta a En revisión en la
  siguiente sincronización; hacer merge la mueve a En curso o Hecho.
- Un fallo de GitHub no deja el tablero vacío.

## Subtareas
- [x] Modelos `Project`, `Board`, `Card`, `CardStatus`, `TaskState`
- [x] `BoardBuilder` con las reglas de columna y de revisión
- [x] Repositorio de proyectos y almacén de snapshots en SQLite (WAL)
- [x] Casos de uso: añadir, editar, borrar, sincronizar uno y sincronizar todos
- [x] Comandos `projects:sync` y `blobs:prune`, y worker con el scheduler
- [x] Tests del `BoardBuilder` y de la sincronización con un `SourceHost` falso
