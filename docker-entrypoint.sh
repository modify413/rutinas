#!/bin/sh
set -e

# SQLite local/archivo: asegurar el fichero. Con Postgres no hace falta.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  export DB_DATABASE="${DB_DATABASE:-/data/database.sqlite}"
  mkdir -p "$(dirname "$DB_DATABASE")"
  [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
fi

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache storage/app/uploads

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
