FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring pdo_pgsql

COPY ./composer.lock ./composer.json /var/www/html/

WORKDIR /var/www/html

COPY . /var/www/html/