# ==========================================
# Stage 1: Build PHP Dependencies
# ==========================================
FROM php:8.3-fpm-alpine as php-build

RUN apk add --no-cache \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    postgresql-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        zip \
        gd \
        intl \
        opcache \
        bcmath \
        pcntl \
        exif

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Install PHP dependencies (tanpa dev package, optimize autoload)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ==========================================
# Stage 2: Build Node.js Assets (Vite)
# ==========================================
FROM node:20-alpine as node-build

WORKDIR /app
COPY package*.json ./
RUN npm ci || npm install

COPY . .

# Ambil vendor & file ziggy.js terbaru dari stage php-build
COPY --from=php-build /var/www/html/vendor ./vendor

RUN npm run build


# ==========================================
# Stage 3: Final Production Runtime Image
# ==========================================
FROM php:8.3-fpm-alpine

# Install runtime package saja untuk menjaga image tetap ringan
RUN apk add --no-cache \
    nginx \
    supervisor \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu \
    libpq \
    curl

WORKDIR /var/www/html

# Copy source code & dependencies dari build stage
COPY --from=php-build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=node-build --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY --from=php-build /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=php-build /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Copy custom config (Nginx, Supervisor, PHP)
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/php-production.ini /usr/local/etc/php/conf.d/app.ini

# Pastikan direktori storage & bootstrap cache ada dan permission sesuai
RUN mkdir -p /var/www/html/storage/framework/cache \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Linked storage untuk public access
RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 80 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://127.0.0.1/ || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
