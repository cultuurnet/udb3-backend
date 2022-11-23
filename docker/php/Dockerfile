FROM php:7.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends git zip unzip libicu-dev libtidy-dev

RUN docker-php-ext-install mysqli pdo_mysql bcmath tidy sockets

RUN docker-php-ext-configure intl && docker-php-ext-install intl

RUN docker-php-ext-configure pcntl --enable-pcntl && docker-php-ext-install pcntl

RUN docker-php-ext-configure gd && docker-php-ext-install gd

RUN curl https://getcomposer.org/download/2.1.3/composer.phar -o /usr/local/bin/composer && chmod +x /usr/local/bin/composer

RUN echo "memory_limit=4096M" > $PHP_INI_DIR/conf.d/memory-limit.ini
