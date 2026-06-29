#!/usr/bin/env bash
#
# Yachay Panel — instalación local de un solo comando.
# Uso:  ./setup.sh
#
set -euo pipefail

cd "$(dirname "$0")"

echo "▶ 1/7  Dependencias PHP (composer)"
composer install --no-interaction --prefer-dist

echo "▶ 2/7  Archivo .env"
if [ ! -f .env ]; then
  cp .env.example .env
  echo "   .env creado desde .env.example"
else
  echo "   .env ya existe, se conserva"
fi

echo "▶ 3/7  Clave de aplicación"
php artisan key:generate --force

echo "▶ 4/7  Base de datos SQLite (desarrollo)"
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite

echo "▶ 5/7  Migraciones y datos de demostración"
php artisan migrate:fresh --seed --force

echo "▶ 6/7  Enlace de almacenamiento (comprobantes/adjuntos)"
php artisan storage:link || true

echo "▶ 7/7  Dependencias y compilación de assets (npm)"
npm install
npm run build

echo ""
echo "✅ Listo. Inicia el servidor con:"
echo "     php artisan serve"
echo ""
echo "   Admin:    http://localhost:8000/admin     · admin@yachay.lat / password"
echo "   Cliente:  http://localhost:8000/client    · cliente@yachay.lat / password"
echo "   Diseño:   http://localhost:8000/constructor"
