# Plan de jolo-PM

jolo-PM es un **visor de estado**: lee las casillas (`- [ ]` / `- [x]`) de los
markdowns de mis repos de GitHub y las enseña como un tablero kanban. Los
markdowns siguen siendo la única fuente de verdad; la app nunca escribe en ellos.

Este plan se sigue a sí mismo: jolo-PM está dado de alta en jolo-PM con la ruta
`docs/plan/E*.md`, así que cada casilla que se marca aquí se ve en el tablero.

## Cómo está organizado

- Un fichero por **épica** (`E01-…md`). Cada uno es una tarjeta del tablero.
- Una épica grande se parte en **sub-épicas** (`E09.1-…md`), cada una en su
  fichero y con su tarjeta. La épica madre queda como índice sin casillas.
- Cada épica tiene **Objetivo**, **Alcance**, **Criterios de aceptación** y la
  lista de **subtareas** como casillas.
- La columna de la tarjeta sale sola de sus casillas:

| Columna | Cuándo |
| --- | --- |
| Por hacer | ninguna casilla marcada en la rama principal |
| En curso | alguna marcada, no todas |
| En revisión | una PR abierta contra la rama principal marca alguna casilla del fichero |
| Hecho | todas marcadas en la rama principal |

## Épicas

| # | Épica | Bloque |
| --- | --- | --- |
| E01 | [Fundación del repo y entorno](E01-fundacion.md) | Infra |
| E02 | [Lectura de tareas en markdown](E02-parser-markdown.md) | Dominio |
| E03 | [Integración con GitHub](E03-github.md) | Infraestructura |
| E04 | [Sincronización y persistencia](E04-sincronizacion.md) | Aplicación |
| E05 | [API HTTP](E05-api.md) | Backend |
| E06 | [Frontend: tablero kanban](E06-frontend-tablero.md) | Frontend |
| E07 | [Frontend: gestión de proyectos](E07-gestion-proyectos.md) | Frontend |
| E08 | [Despliegue y CI](E08-despliegue-ci.md) | Infra |
| E09 | [Mejoras futuras](E09-mejoras-futuras.md) | Backlog |
| E09.1 | ↳ [Webhook de GitHub](E09.1-webhook-github.md) | Backlog |
| E09.2 | ↳ [Tarjeta por sección](E09.2-tarjeta-por-seccion.md) | Dominio |
| E09.3 | ↳ [Historial de progreso](E09.3-historial-progreso.md) | Backlog |
| E09.4 | ↳ [Trabajo fuera de la rama principal](E09.4-trabajo-fuera-de-main.md) | Backlog |
| E09.5 | ↳ [Varios tokens de GitHub](E09.5-varios-tokens.md) | Backlog |
| E09.6 | ↳ [Passkeys (WebAuthn)](E09.6-passkeys.md) | Backlog |
| E09.7 | ↳ [Widget para Glance](E09.7-widget-glance.md) | Backlog |
| E10 | [Login con 2FA](E10-login-2fa.md) | Seguridad |
| E11 | [Despliegue automático](E11-despliegue-automatico.md) | Infra |
| E12 | [Idiomas: castellano e inglés](E12-idiomas.md) | Frontend |
| E13 | [Calidad del backend](E13-calidad-backend.md) | Calidad |

Este README no lleva casillas a propósito: el progreso global lo calcula el
tablero, no hay que mantenerlo a mano.
