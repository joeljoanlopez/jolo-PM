# E05 — API HTTP

## Objetivo
Exponer proyectos y tableros como JSON para el frontend, con errores claros.

## Alcance

| Método | Ruta | Qué hace |
| --- | --- | --- |
| GET | `/api/health` | Comprobación de vida |
| GET | `/api/repositories` | Repos de GitHub a los que tiene acceso el token |
| GET | `/api/projects` | Proyectos con su resumen (recuentos por columna, progreso, última sync, error) |
| POST | `/api/projects` | Alta: `{repository: "owner/name", branch?, paths[], name?}`; sincroniza al momento |
| PATCH | `/api/projects/{id}` | Cambia rama, globs o nombre; vuelve a sincronizar |
| DELETE | `/api/projects/{id}` | Baja (no toca nada en GitHub) |
| POST | `/api/projects/{id}/sync` | Sincronización manual |
| GET | `/api/projects/{id}/board` | Tablero completo |

Errores con forma fija `{"error": {"code", "message"}}`: 422 validación o repo
sin acceso, 404 proyecto inexistente, 409 repo ya dado de alta, 502 GitHub caído.

## Criterios de aceptación
- Todas las rutas probadas con `curl` contra el contenedor.

## Subtareas
- [ ] Front controller con Slim y manejador de errores
- [ ] `ProjectController` y `RepositoryController`
- [ ] Validación de entrada y traducción de excepciones a códigos HTTP
- [ ] Prueba con `curl` de todas las rutas
