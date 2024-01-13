FROM php:8.2-fpm

RUN docker-php-ext-install pdo_mysql mbstring

RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring

COPY ../composer.lock ../composer.json /var/www/html/

WORKDIR /var/www/html

RUN composer install

COPY . /var/www/html/

EXPOSE 9000
CMD ["php-fpm"]