FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring pdo_pgsql

WORKDIR /var/www/html