# E03 — Integración con GitHub

## Objetivo
Leer de GitHub todo lo que necesita el tablero, con un único token de solo
lectura y gastando las mínimas peticiones posibles.

## Alcance
- Puerto de dominio `SourceHost` con: datos del repo, repos accesibles, ficheros
  markdown de una rama, contenido de un blob, PRs abiertas contra una rama y
  ficheros de una PR.
- Adaptador `GitHubSourceHost` sobre la API REST con el cliente `Http` de
  Laravel, con paginación por cabecera `Link`.
- El contenido se pide **por SHA de blob** (`/git/blobs/{sha}`), que es
  inmutable, así que se cachea en SQLite para siempre: una sincronización sin
  cambios no descarga ningún fichero. La caché es otro puerto (`ContentCache`)
  y el decorador `CachingSourceHost` sirve para cualquier `SourceHost`.
- Errores tipados: repo inexistente o sin acceso (404) frente a GitHub caído o
  token inválido.

## Criterios de aceptación
- Con un token válido se listan los repos propios y de organizaciones a los que
  tengo acceso, públicos y privados.
- Una segunda sincronización de un proyecto sin cambios solo hace las peticiones
  de árbol y de PRs.

## Subtareas
- [x] Puerto `SourceHost` y modelos `RepositoryInfo`, `FileRef`, `PullRequest`
- [x] Cliente HTTP con token, paginación y errores tipados
- [x] Adaptador `GitHubSourceHost`
- [x] Puerto `ContentCache`, adaptador Eloquent y decorador `CachingSourceHost`
- [x] Tests del adaptador con `Http::fake` (paginación, base64, 401, 404, límite)
