FROM php:7.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends git zip unzip libicu-dev libtidy-dev

RUN docker-php-ext-install mysqli pdo_mysql bcmath tidy sockets

RUN docker-php-ext-configure intl && docker-php-ext-install intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo "memory_limit=4096M" > $PHP_INI_DIR/conf.d/memory-limit.ini
