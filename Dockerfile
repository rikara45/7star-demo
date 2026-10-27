# Demo image untuk 7star-demo (Laravel 12 + SQLite, tanpa MySQL).
# Basis: php:8.3-apache supaya document root langsung ke public/.
FROM php:8.3-apache

# Ekstensi yang dibutuhkan Laravel + dompdf (export PDF) + SQLite.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libonig-dev libsqlite3-dev unzip curl \
    && docker-php-ext-install pdo_sqlite mbstring zip gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# --no-dev: tidak perlu phpunit/pint di demo. public/build sudah ter-commit,
# jadi Vite tidak perlu dijalankan di server.
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod +x docker/entrypoint.sh

EXPOSE 80

CMD ["docker/entrypoint.sh"]
