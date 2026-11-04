# jolo-PM — backend

API en Laravel con arquitectura hexagonal. Ver
[`docs/architecture.md`](../docs/architecture.md) y el [README](../README.md).

Sin PHP en el host, todo se ejecuta con Docker:

```sh
# tests
docker run --rm -u $(id -u):$(id -g) -v $PWD:/app -w /app composer:2 \
  sh -c 'composer install -q && vendor/bin/phpunit'
```
