#!/bin/sh
set -e

# SQLite vive en el disco persistente de Render para no perder datos
export DB_DATABASE="${DB_DATABASE:-/data/database.sqlite}"
mkdir -p "$(dirname "$DB_DATABASE")"
[ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache

# Generar APP_KEY si no viene definida como variable de entorno
if [ -z "$APP_KEY" ]; then
  export APP_KEY="$(php artisan key:generate --show)"
  echo "APP_KEY generada automáticamente."
fi

export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:8000}}"

php artisan migrate --force
php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
