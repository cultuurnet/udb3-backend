FROM php:7.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends git zip unzip libicu-dev libtidy-dev zlib1g-dev libpng-dev libzip-dev supervisor libfontconfig1

RUN docker-php-ext-install mysqli pdo_mysql bcmath tidy sockets

RUN docker-php-ext-configure zip && docker-php-ext-install zip

RUN docker-php-ext-configure intl && docker-php-ext-install intl

RUN docker-php-ext-configure pcntl --enable-pcntl && docker-php-ext-install pcntl

RUN docker-php-ext-configure gd && docker-php-ext-install gd

RUN pecl install -o -f redis &&  rm -rf /tmp/pear &&  docker-php-ext-enable redis

RUN curl https://getcomposer.org/download/2.6.6/composer.phar -o /usr/local/bin/composer && chmod +x /usr/local/bin/composer

RUN if [ "$(uname -m)" = "aarch64" ]; then \
      curl https://www.princexml.com/download/prince-15.4.1-linux-generic-aarch64.tar.gz -o /root/prince-15.4.1-linux-generic-aarch64.tar.gz && \
      tar -xzf /root/prince-15.4.1-linux-generic-aarch64.tar.gz -C /root && \
      cd /root/prince-15.4.1-linux-generic-aarch64 && \
      ./install.sh </dev/null; \
    else \
      curl https://www.princexml.com/download/prince-20241011-linux-generic-x86_64.tar.gz -o /root/prince-20241011-linux-generic-x86_64.tar.gz && \
      tar -xzf /root/prince-20241011-linux-generic-x86_64.tar.gz -C /root && \
      cd /root/prince-20241011-linux-generic-x86_64 && \
      ./install.sh </dev/null; \
    fi


RUN echo "memory_limit=4096M" > $PHP_INI_DIR/conf.d/memory-limit.ini
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" > $PHP_INI_DIR/conf.d/error_reporting.ini

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
