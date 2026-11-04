# Dependencias en su propia etapa: un cambio en app/ no invalida la caché de
# composer, que es lo que más tarda.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi

FROM php:8.4-fpm-alpine AS runtime
# La imagen oficial ya trae curl, mbstring y pdo_sqlite: no hay que compilar nada.
WORKDIR /app
COPY --from=vendor /app /app
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/php.ini /usr/local/etc/php/conf.d/jolo-pm.ini

# El contenedor corre con el uid del host (PUID) para que la base de datos
# quede a su nombre; storage/ y bootstrap/cache/ tienen que ser escribibles
# para cualquiera porque ese uid no se conoce al construir.
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p /data storage/framework/cache/data storage/logs bootstrap/cache \
    && chmod -R a+rwX storage bootstrap/cache /data

ENTRYPOINT ["entrypoint"]
EXPOSE 9000
CMD ["php-fpm"]
