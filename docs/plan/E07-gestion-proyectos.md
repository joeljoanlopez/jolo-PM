# E07 — Frontend: gestión de proyectos

## Objetivo
Conectar, ajustar y quitar proyectos desde la interfaz, eligiendo entre los
repos a los que tengo acceso.

## Alcance
- Formulario de alta: buscador de repos (de `/api/repositories`), rama opcional,
  globs (uno por línea, `docs/**/*.md` por defecto) y nombre opcional.
- Edición de los mismos campos desde el tablero del proyecto, y baja con
  confirmación.
- Los errores del backend (repo sin acceso, ya dado de alta, glob vacío) se
  muestran junto al formulario.

## Criterios de aceptación
- Dar de alta PKManagement con `docs/learnPlan/*.md` y jolo-PM con
  `docs/plan/E*.md` solo desde la interfaz.

## Subtareas
- [ ] Formulario de alta con buscador de repos
- [ ] Edición de rama, globs y nombre
- [ ] Baja con confirmación
- [ ] Mensajes de error del backend en el formulario
