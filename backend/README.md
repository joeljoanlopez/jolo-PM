# jolo-PM — backend

API en Laravel con arquitectura hexagonal. Ver
[`docs/architecture.md`](../docs/architecture.md) y el [README](../README.md).

## Calidad

Los mismos scripts de Composer que ejecuta el CI, cada uno en su paso:

| Comando | Qué hace |
| --- | --- |
| `composer lint` | Estilo PSR-12 con PHPCS (`phpcs.xml`) sobre `app/`, `tests/`, `database/` y `routes/`. Los avisos también cuentan. |
| `composer lint:fix` | Arregla lo que PHPCS sabe arreglar solo. |
| `composer analyse` | PHPStan + Larastan en nivel 8 sobre `app/` (`phpstan.neon`), sin baseline. El nivel solo puede subir. |
| `composer test` | Todas las suites de PHPUnit. |
| `composer coverage` | Tests con cobertura (PCOV) y fallo si baja del **70 % de líneas**. Informe en `build/coverage/` (HTML y Clover). |

PHPCS es la única fuente de estilo: Pint se ha quitado para que no haya dos
herramientas con reglas distintas.

### Suites de tests

- `Unit`: dominio puro, sin arrancar Laravel, y el test de arquitectura.
- `Feature`: API y adaptador de GitHub con SQLite en memoria.
- `Integration`: los adaptadores contra **SQLite en fichero** con las
  migraciones de verdad (repositorios Eloquent, comandos de consola y su
  programación) y los flujos completos por HTTP con GitHub falso.

Una suite suelta: `composer test -- --testsuite Integration`.

### Sin PHP en el host

Todo funciona igual dentro de Docker. La imagen `composer:2` no trae PCOV, así
que `composer coverage` solo va en el CI o con un PHP que lo tenga:

```sh
docker run --rm -u $(id -u):$(id -g) -v $PWD:/app -w /app composer:2 \
  sh -c 'composer install -q && composer lint && composer analyse && composer test'
```

### Hook de pre-commit

Opcional, una vez por copia del repo:

```sh
git config core.hooksPath .githooks
```

Antes de cada commit con ficheros PHP del backend, pasa PHPCS sobre ellos y
PHPStan sobre los de `app/`. Si el commit solo toca el frontend o la
documentación, no hace nada. Usa el `php` del sistema o, si no hay, Docker; necesita
`backend/vendor` instalado. Los tests y la cobertura se quedan en el CI. Para
saltarlo: `git commit --no-verify`.
