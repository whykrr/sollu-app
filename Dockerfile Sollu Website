# Stage 1: Build PHP dependencies
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
        opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction


# Stage 2: Build Node.js assets
FROM node:20-alpine as node-build

WORKDIR /app
COPY package*.json ./
RUN npm install

COPY . .

#PENTING: Ambil folder vendor dari stage php-build agar Ziggy ditemukan oleh Vite
COPY --from=php-build /var/www/html/vendor ./vendor

RUN npm run build

# Stage 3: Final Production Image
FROM php:8.3-fpm-alpine

# Install runtime dependencies saja
RUN apk add --no-cache \
    nginx supervisor libzip libpng libjpeg-turbo freetype icu libpq

WORKDIR /var/www/html

# Copy aplikasi dari stage php-build
COPY --from=php-build --chown=www-data:www-data /var/www/html /var/www/html

# Copy built assets dari stage node-build
COPY --from=node-build --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Copy PHP extensions dari stage php-build
COPY --from=php-build /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=php-build /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Copy konfigurasi custom
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/php-production.ini /usr/local/etc/php/conf.d/app.ini

# Permission untuk Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# linked storage untuk akses file yang diupload
RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
