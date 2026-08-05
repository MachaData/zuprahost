# Imagen de producción para zupraHost.
#
# Se usa un Dockerfile en lugar de la detección automática de Railway para
# que la versión de PHP, las extensiones y el arranque estén escritos aquí y
# no dependan de cómo interprete el constructor el composer.json.
#
# FrankenPHP sirve la aplicación directamente: un solo proceso, sin nginx ni
# php-fpm que coordinar.

FROM dunglas/frankenphp:php8.4-bookworm

# intl lo exige Filament y zip openspout; el resto son las habituales de
# Laravel en producción.
RUN install-php-extensions \
        intl \
        zip \
        pdo_mysql \
        opcache \
        gd \
        exif \
        bcmath

# Node solo hace falta para compilar los assets; se elimina al terminar.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl ca-certificates git unzip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Las dependencias van antes que el código para que Docker reutilice la capa
# mientras composer.lock y package-lock.json no cambien.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && rm -rf node_modules \
    && apt-get purge -y nodejs && apt-get autoremove -y

# public/storage viene versionado como enlace relativo y apunta aquí; el
# volumen de Railway se monta sobre esta ruta.
RUN mkdir -p storage/framework/sessions storage/framework/views \
             storage/framework/cache storage/logs \
             storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

# No se ejecuta ningún artisan durante el build: sin APP_KEY ni base de datos
# fallaría, y cachear la configuración aquí congelaría variables vacías. Todo
# eso ocurre en el arranque, cuando el entorno ya existe.

# Railway inyecta PORT; 8080 es el valor por defecto en local.
ENV PORT=8080
ENV SERVER_NAME=":8080"

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
