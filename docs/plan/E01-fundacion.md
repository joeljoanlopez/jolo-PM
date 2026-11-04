# E01 — Fundación del repo y entorno

## Objetivo
Tener el monorepo creado, con backend PHP y frontend React + TypeScript que
arrancan en Docker y se pueden desarrollar sin instalar PHP en el host.

## Alcance
- Repo privado `joeljoanlopez/jolo-PM`, clonado en `~/Docker/jolo-PM`.
- `backend/`: PHP 8.4 + **Laravel 13**, con arquitectura hexagonal: `app/Domain` y
  `app/Application` son PHP puro; Laravel solo vive en `app/Infrastructure`
  (controladores, Eloquent, cliente HTTP, consola) y en el `ServiceProvider` que
  conecta puertos con adaptadores. Ver [arquitectura](../architecture.md).
- `frontend/`: React 18 + TypeScript + Vite + Tailwind 4 + Lucide, con la misma
  separación: `domain`, `ports`, `infrastructure` (adaptador HTTP) y `ui`.
- `compose.yaml` con tres servicios: `frontend` (nginx), `backend` (PHP-FPM) y
  `worker` (sincronización periódica). Misma convención que el resto de stacks
  del servidor: puerto solo en `127.0.0.1` y labels de `nginx-autoconf`.

## Criterios de aceptación
- `docker compose up -d --build` levanta los tres servicios y `GET /api/health`
  responde `{"status":"ok"}`.
- Cambiar GitHub, SQLite o la caché por otra cosa no obliga a tocar `Domain` ni
  `Application`; un test lo vigila.
- Ningún secreto en el repo: el token de GitHub va en `.env` (ignorado), con un
  `.env.example` documentado.

## Subtareas
- [x] Crear el repo local y la estructura de carpetas
- [x] Plan en `docs/plan/` con una épica por fichero
- [x] Esqueleto del backend (Laravel sin vistas, usuarios ni colas)
- [x] Documento de arquitectura hexagonal (`docs/architecture.md`)
- [x] Test de arquitectura: Domain y Application no importan el framework
- [x] Esqueleto del frontend (Vite, TypeScript estricto, Tailwind, Lucide)
- [x] Dockerfiles multi-etapa de backend y frontend
- [x] `compose.yaml`, `.env.example` y `.gitignore`
- [ ] Crear el repo privado en GitHub y subir `main`
