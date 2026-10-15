# Testumgebung „wie One.com“: PHP 8.4 + Apache, Document-Root = Projektordner,
# die Root-.htaccess leitet nach public/ weiter (genau wie später im Webspace).

# 1) PHP-Abhängigkeiten ohne Dev-Pakete
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# 2) Frontend-Build (Tailwind/Vite)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3) Laufzeit
FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev mariadb-client unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip bcmath exif opcache \
    && a2enmod rewrite headers deflate expires \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-armory.ini
COPY docker/php/apache.conf /etc/apache2/conf-enabled/zz-armory.conf

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN rm -f public/hot .env && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public storage/app/private/documents storage/fonts bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/armory-entrypoint
RUN chmod +x /usr/local/bin/armory-entrypoint
ENTRYPOINT ["armory-entrypoint"]
CMD ["apache2-foreground"]
