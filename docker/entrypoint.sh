#!/bin/sh
# Entrypoint demo: siapkan .env, SQLite, migrasi + data contoh, lalu jalankan Apache.
set -e
cd /var/www/html

# Direktori runtime Laravel (tidak ikut git/.dockerignore, jadi dibuat di sini).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs bootstrap/cache storage/app/public/portfolios

if [ ! -f .env ]; then
    cp .env.example .env
fi

# APP_KEY hanya dibuat sekali per container (demo: aman dibuat ulang tiap boot).
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# SQLite self-contained: ikut image, tidak butuh service database.
DB_FILE="${DB_DATABASE:-/var/www/html/database/demo.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"
# pastikan .env menunjuk ke file yang sama
if grep -q '^DB_DATABASE=' .env; then
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_FILE|" .env
else
    printf 'DB_CONNECTION=sqlite\nDB_DATABASE=%s\n' "$DB_FILE" >> .env
fi

# Data contoh selalu di-reset saat deploy supaya demo konsisten.
php artisan migrate:fresh --seed --force

php artisan storage:link || true
php artisan config:clear

chown -R www-data:www-data storage bootstrap/cache database

apache2-foreground
