#!/usr/bin/env bash
#
# zupraHost Panel — instalación local de un solo comando.
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

echo "▶ 4/7  Base de datos"
# Lee una clave del archivo .env (sin comillas).
env_get() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed 's/^"//; s/"$//'; }

DB_CONNECTION="$(env_get DB_CONNECTION)"
DB_CONNECTION="${DB_CONNECTION:-sqlite}"

if [ "$DB_CONNECTION" = "mysql" ]; then
  echo "   Usando MySQL · creando la base de datos si no existe…"
  DB_HOST="$(env_get DB_HOST)" DB_PORT="$(env_get DB_PORT)" \
  DB_DATABASE="$(env_get DB_DATABASE)" DB_USERNAME="$(env_get DB_USERNAME)" \
  DB_PASSWORD="$(env_get DB_PASSWORD)" php -r '
    $h=getenv("DB_HOST")?:"127.0.0.1"; $p=getenv("DB_PORT")?:"3306";
    $db=getenv("DB_DATABASE")?:"zuprahost"; $u=getenv("DB_USERNAME")?:"root"; $pw=getenv("DB_PASSWORD")?:"";
    try {
      $pdo=new PDO("mysql:host=$h;port=$p", $u, $pw);
      $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
      echo "   ✓ Base de datos \"$db\" lista\n";
    } catch (Throwable $e) {
      fwrite(STDERR, "   ✗ No se pudo conectar a MySQL: ".$e->getMessage()."\n");
      fwrite(STDERR, "     Revisa DB_* en .env y que el servidor MySQL esté corriendo.\n");
      exit(1);
    }
  '
else
  echo "   Usando SQLite (desarrollo)"
  mkdir -p database
  [ -f database/database.sqlite ] || touch database/database.sqlite
fi

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
echo "   Admin:    http://localhost:8000/admin     · admin@zuprahost.com / password"
echo "   Cliente:  http://localhost:8000/client    · cliente@zuprahost.com / password"
echo "   Diseño:   http://localhost:8000/constructor"
