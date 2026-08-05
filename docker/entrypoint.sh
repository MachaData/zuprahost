#!/bin/sh
set -e

# Railway inyecta PORT y monta el volumen justo antes de este script, así que
# todo lo que dependa del entorno o del disco montado se resuelve aquí y no
# en el Dockerfile.
PORT="${PORT:-8080}"

# El volumen se monta encima de storage/app/public y llega vacío, tapando lo
# que se creó durante el build. Hay que rehacer la estructura y los permisos
# después del montaje o Laravel no puede escribir sesiones, vistas ni subidas.
echo "==> Preparando almacenamiento"
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache/data \
         storage/logs \
         storage/app/public \
         bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# public/storage viene versionado como enlace relativo; se rehace por si el
# montaje lo dejó apuntando a un directorio que ya no existe.
php artisan storage:link --force >/dev/null 2>&1 || true

echo "==> Migrando base de datos"
php artisan migrate --force

# Las cachés se generan ahora, con las variables ya presentes. Hacerlo en el
# build las congelaría vacías.
echo "==> Preparando cachés"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# FrankenPHP toma la dirección de SERVER_NAME; si quedara el valor del build
# escucharía en un puerto que Railway no está observando.
export SERVER_NAME=":${PORT}"

echo "==> Sirviendo en el puerto ${PORT}"
exec frankenphp php-server \
    --root /app/public \
    --listen ":${PORT}" \
    --access-log
