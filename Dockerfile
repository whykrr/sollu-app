# ==========================================
# Stage 1: Build PHP Vendor Dependencies
# ==========================================
FROM php:8.3-fpm-alpine AS php-build

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Salin manifest terlebih dahulu agar layer composer ter-cache selama lockfile tidak berubah
COPY composer.json composer.lock ./

# Unduh dependensi tanpa script & autoloader (BuildKit cache mount untuk mempercepat unduhan)
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Salin source code aplikasi
COPY . .

# Generate optimized classmap autoloading & package discovery
RUN composer dump-autoload --no-dev --optimize --no-interaction


# ==========================================
# Stage 2: Build Node.js Assets (Vite)
# ==========================================
FROM node:20-alpine AS node-build

WORKDIR /app

# Salin package manifest terlebih dahulu untuk npm caching
COPY package*.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci || npm install

COPY . .

# Ambil vendor (termasuk Ziggy) dari stage php-build
COPY --from=php-build /var/www/html/vendor ./vendor

RUN npm run build


# ==========================================
# Stage 3: Final Production Runtime Image
# ==========================================
FROM php:8.3-fpm-alpine

# Patch OS level libraries (CVE/keamanan) dan install runtime server
RUN apk update && apk upgrade --no-cache \
    && apk add --no-cache \
        nginx \
        supervisor \
        curl

# Install PHP extensions menggunakan extension-installer resmi
# Mengompilasi ekstensi, menginstal runtime libraries yang tepat,
# dan otomatis menghapus dev-headers & compiler tools dalam satu layer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
    intl \
    opcache \
    bcmath \
    pcntl \
    exif

WORKDIR /var/www/html

# Copy source code & dependencies bersih dari build stage
COPY --from=php-build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node-build --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Copy custom config (Nginx, Supervisor, PHP)
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/php-production.ini /usr/local/etc/php/conf.d/app.ini

# Pastikan direktori storage & bootstrap cache ada dan permission sesuai
RUN mkdir -p /var/www/html/storage/framework/cache \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/storage/logs/query \
             /var/www/html/storage/app/private/exports \
             /var/www/html/storage/app/private/imports \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Linked storage untuk public access
RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage

# Setup crontab untuk menjalankan Laravel schedule:run via Linux crond setiap menit
RUN echo "* * * * * su -s /bin/sh www-data -c 'php /var/www/html/artisan schedule:run --no-interaction' >> /var/www/html/storage/logs/schedule.log 2>&1" > /etc/crontabs/root \
    && chmod 0600 /etc/crontabs/root

EXPOSE 80 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://127.0.0.1/up || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
