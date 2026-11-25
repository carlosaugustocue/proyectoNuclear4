# Imagen de producción de la API y del worker (ADR-0011, ADR-0013).
# Contexto de construcción: raíz del repositorio.
#   API:    comando por defecto (migra y sirve en $PORT)
#   Worker: php artisan queue:work --queue=domain-events --tries=3

FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader --ignore-platform-reqs
COPY backend/ ./
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

FROM php:8.3-cli-alpine
RUN apk upgrade --no-cache \
    && apk add --no-cache postgresql-libs libzip \
    && apk add --no-cache --virtual .build postgresql-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql zip bcmath opcache pcntl \
    && apk del .build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
WORKDIR /var/www/html
COPY --from=vendor /app ./
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app \
    && chown -R app:app storage bootstrap/cache
USER app

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr QUEUE_CONNECTION=database
EXPOSE 8080
# `artisan serve` (servidor embebido de PHP) basta para la demo académica (R-04);
# en producción real se cambiaría por php-fpm + nginx o FrankenPHP.
# --no-reload es necesario para que PHP_CLI_SERVER_WORKERS (procesos concurrentes) tenga efecto:
# sin él Laravel ignora la variable y atiende una petición a la vez (medido en pruebas-calidad/, EC-04).
CMD ["sh", "-c", "php artisan config:cache && php artisan migrate --force && (php artisan ingesta:sipsa || echo \"AVISO: la ingesta de SIPSA falló; la API arranca con el catálogo actual\") && php artisan serve --no-reload --host=0.0.0.0 --port=${PORT:-8080}"]
