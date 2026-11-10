FROM php:8.3-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    git \
    unzip \
    linux-headers \
    $PHPIZE_DEPS

# Extensiones PHP requeridas por el proyecto
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    bcmath \
    opcache \
    pcntl

# Limpiar dependencias de compilación
RUN apk del $PHPIZE_DEPS linux-headers

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Usuario no-root con mismo UID que el host (evita problemas de permisos en volúmenes)
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /var/www/html

USER app
