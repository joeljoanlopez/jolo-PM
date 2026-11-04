#!/bin/sh
# Prepara la app antes de arrancar el proceso (php-fpm o el scheduler).
set -eu

DB="${DB_DATABASE:-/data/jolo-pm.sqlite}"
[ -f "$DB" ] || touch "$DB"

# La configuración se cachea aquí y no en el build porque depende del entorno
# (token, cron…), que solo existe al arrancar.
php artisan config:cache --quiet
php artisan route:cache --quiet

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    php artisan migrate --force --no-interaction
else
    # El worker espera a que el backend haya migrado, para no correr dos
    # migraciones a la vez sobre el mismo fichero SQLite.
    until php artisan migrate:status 2>/dev/null | grep -q 'Ran' \
        && ! php artisan migrate:status 2>/dev/null | grep -q 'Pending'; do
        echo "Esperando a las migraciones del backend…"
        sleep 2
    done
fi

exec "$@"
