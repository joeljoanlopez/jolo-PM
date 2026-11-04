# E06 — Frontend: tablero kanban

## Objetivo
Ver de un vistazo cómo va cada proyecto y entrar al detalle de cualquier
tarjeta sin salir del tablero.

## Alcance
- **Inicio**: una tarjeta por proyecto con barra de progreso global, recuento
  por columna, hora de la última sincronización y error si lo hay.
- **Tablero** de un proyecto: cuatro columnas (Por hacer, En curso, En revisión,
  Hecho). Cada tarjeta muestra título, ruta, barra de progreso (hecho + en
  revisión), `hechas/total` y las PRs que la afectan.
- Al abrir una tarjeta se ven sus tareas agrupadas por sección, con sangría,
  el estado de cada una y enlace a la PR que la marca. Enlace al fichero en
  GitHub.
- Botón **Sincronizar** y refresco automático cada minuto.
- Funciona en móvil (columnas apiladas) y en modo oscuro.

## Criterios de aceptación
- El tablero de PKManagement y el de jolo-PM se ven correctamente.

## Subtareas
- [x] Tipos de la API y cliente `fetch` tipado
- [x] Enrutado por hash (`#/`, `#/projects/{id}`) sin dependencias extra
- [x] Vista de inicio con resumen de proyectos
- [x] Tablero de columnas y tarjetas con barra de progreso
- [x] Detalle de tarjeta con tareas por sección y enlaces a PRs
- [x] Sincronización manual, refresco automático y estados de carga y error
- [x] Diseño adaptable y modo oscuro
