#!/bin/sh
set -e

# Las migraciones corren aquí y no en un pre-deploy de Railway porque ese
# comando se ejecuta en un contenedor aparte; así queda todo en un sitio.
echo "==> Migrando base de datos"
php artisan migrate --force

# La caché de configuración se genera en el arranque, cuando las variables de
# entorno ya existen. Hacerlo en el build las congelaría vacías.
echo "==> Preparando cachés"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# El enlace viene versionado, pero si el volumen se monta encima puede faltar.
php artisan storage:link --force >/dev/null 2>&1 || true

echo "==> Sirviendo en el puerto ${PORT}"
exec frankenphp php-server \
    --root /app/public \
    --listen ":${PORT}" \
    --access-log
