# E02 — Lectura de tareas en markdown

## Objetivo
Convertir un fichero markdown en una lista de tareas con su estado, su sección
y su nivel de anidación, sin exigir ningún formato especial al autor.

## Alcance
- Una tarea es un elemento de lista con casilla: `- [ ]`, `* [x]`, `+ [X]`,
  `1. [ ]`. Lo demás se ignora.
- El **título** del documento es el primer `# H1`; si no hay, el nombre del
  fichero.
- Cada tarea recuerda la **sección** (último encabezado `##`–`######` por encima)
  y su **profundidad** según la sangría.
- Las casillas dentro de bloques de código (```` ``` ```` o `~~~`) no cuentan.
- Cada tarea tiene una **clave estable** (texto normalizado + nº de aparición)
  para poder emparejar la misma tarea entre la rama principal y una PR.
- Selección de ficheros por **globs**: `docs/plan/*.md`, `docs/**/*.md`, `TODO.md`.

## Criterios de aceptación
- Los capítulos de `PKManagement/docs/learnPlan` se leen con el número correcto
  de casillas.
- Cambiar un enlace `[texto](url)` por otro con el mismo texto no rompe el
  emparejamiento de la tarea.

## Subtareas
- [x] Modelo de dominio: `Task`, `ParsedDocument`
- [x] Parser de casillas con sección, profundidad y línea
- [x] Ignorar bloques de código
- [x] Clave estable normalizada (sin enlaces, sin formato, en minúsculas)
- [x] `GlobMatcher` con `*`, `**` y `?`
- [x] Tests unitarios del parser y del glob
