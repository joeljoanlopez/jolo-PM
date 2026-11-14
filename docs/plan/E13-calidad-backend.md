# E13 — Calidad del backend

## Objetivo
Que el CI frene más errores del backend antes del merge. Ahora mismo solo pasa
PHPUnit (suites `Unit` y `Feature`), sin análisis estático, sin comprobar el
estilo y sin medir la cobertura.

## Alcance
- **PHPStan** con Larastan, configurado en `backend/phpstan.neon` y analizando
  `app/`. Se empieza en el nivel más alto que pase sin baseline o con una
  baseline pequeña, y la baseline solo puede encoger. **Queda en nivel 8 sin
  baseline**: el 9 da unos 44 errores, casi todos `mixed` del JSON de GitHub y
  del contenedor.
- **PHPCS** con el estándar PSR-12, configurado en `backend/phpcs.xml` y
  aplicado a `app/`, `tests/`, `database/` y `routes/`. **Sustituye a Pint**,
  que estaba en `require-dev` sin usarse, para que no haya dos fuentes de
  estilo que se contradigan. Los avisos (líneas de más de 120 caracteres)
  también hacen fallar el CI.
- **Cobertura** con PCOV en CI. El umbral es moderado, **70 % de líneas**, y
  el job falla si se baja de ahí. El informe queda como artefacto del job.
- **Tests de integración** de los adaptadores contra infraestructura real o
  local:
  - Repositorios Eloquent (`EloquentProjectRepository`, `EloquentBoardStore`,
    `EloquentContentCache`) contra una base SQLite **en fichero** con
    migraciones, el mismo motor que se usa en producción.
  - Comandos de consola y su programación en el scheduler.
- **Tests de aceptación** de extremo a extremo por HTTP, con el `SourceHost`
  falso, que recorren los flujos principales:
  - Alta de proyecto → sincronización → el tablero devuelve las tarjetas en
    sus columnas.
  - Una casilla marcada en una PR abierta mueve la tarjeta a «En revisión».
  - Editar y borrar un proyecto, y respuestas a peticiones con datos no
    válidos. Las peticiones **sin sesión** se prueban con el login de E10:
    hoy la API no tiene sesión.
- Una suite nueva de PHPUnit, `Integration`, separada de `Unit` y `Feature`
  para poder ejecutarla aparte.
- En el CI, cada herramienta va en su propio paso para que se vea qué ha
  fallado.
- **Hook de pre-commit** versionado en `.githooks/pre-commit` y activado con
  `git config core.hooksPath .githooks`, sin dependencias nuevas. Solo actúa si
  hay ficheros PHP de `backend/` en el commit y pasa sobre ellos PHPCS y
  PHPStan. Los tests y la cobertura se quedan en el CI, porque son demasiado
  lentos para cada commit. Se puede saltar con `git commit --no-verify`, y el CI
  sigue siendo la barrera definitiva.

## Criterios de aceptación
- Una PR con un error de tipos que PHPStan detecta deja el CI en rojo.
- Una PR que rompe PSR-12 deja el CI en rojo.
- Una PR que baja la cobertura de líneas del 70 % deja el CI en rojo.
- Los casos de uso de `app/Application` y los adaptadores de persistencia
  tienen al menos un test que los ejercita sin dobles en la capa de
  persistencia.
- `composer test`, `composer analyse` y `composer lint` funcionan en local igual
  que en el CI.
- Con el hook activado, un commit con un fichero PHP que rompe PSR-12 o PHPStan
  se rechaza. Un commit que solo toca el frontend o la documentación no lo
  ejecuta.

## Subtareas
- [x] PHPStan + Larastan: config, nivel inicial, baseline y script `composer analyse`
- [x] PHPCS con PSR-12: config, arreglos de estilo y script `composer lint`
- [x] Decidir qué hacer con Pint: se quita
- [x] Cobertura con PCOV en CI, umbral del 70 % de líneas e informe como artefacto
- [x] Suite `Integration` en `phpunit.xml`
- [x] Tests de integración de los repositorios Eloquent contra SQLite en fichero
- [x] Tests de integración de los comandos de consola y el scheduler
- [x] Tests de aceptación por HTTP de los flujos principales del tablero
- [x] Pasos separados en `ci.yml` para análisis, estilo, tests y cobertura
- [x] Hook `.githooks/pre-commit` con PHPCS y PHPStan sobre los PHP del commit
- [x] Sección en `backend/README.md` con los comandos de calidad y cómo activar el hook
