#!/bin/sh
set -e

# Una misma carpeta persistente permite trasladar la instalación a otro equipo.
export APP_DATA_DIR="${APP_DATA_DIR:-/data}"
export MEDIA_LOCAL_ROOT="${MEDIA_LOCAL_ROOT:-$APP_DATA_DIR/media}"
mkdir -p "$APP_DATA_DIR" "$MEDIA_LOCAL_ROOT"

# SQLite local/archivo: asegurar el fichero. Con Postgres no hace falta.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  export DB_DATABASE="${DB_DATABASE:-$APP_DATA_DIR/database.sqlite}"
  mkdir -p "$(dirname "$DB_DATABASE")"
  [ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"
fi

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache

# Aplicar el entorno actual antes de ejecutar migraciones o generar la clave.
php artisan config:clear

# Conservar la clave entre reinicios y al trasladar la carpeta de datos.
if [ -z "$APP_KEY" ]; then
  if [ ! -s "$APP_DATA_DIR/app.key" ]; then
    (umask 077; php artisan key:generate --show > "$APP_DATA_DIR/app.key")
  fi
  APP_KEY="$(cat "$APP_DATA_DIR/app.key")"
  export APP_KEY
else
  (umask 077; printf '%s\n' "$APP_KEY" > "$APP_DATA_DIR/app.key")
fi

export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:8000}}"

php artisan migrate --force
php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
