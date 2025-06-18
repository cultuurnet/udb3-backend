FROM php:8.1-apache

ARG ENABLE_XDEBUG

# Packages can be found here https://www.debian.org/distrib/packages#search_packages
RUN apt-get update && apt-get install -y --no-install-recommends git zip unzip libicu-dev libtidy-dev zlib1g-dev \
    libpng-dev libzip-dev supervisor fontconfig-config fonts-dejavu-core libdeflate0 libfontconfig1 libfreetype6 \
    libgif7 libjbig0 libjpeg62-turbo liblcms2-2 libwebpdemux2 sensible-utils ucf gdebi-core

RUN docker-php-ext-install mysqli pdo_mysql bcmath tidy sockets

RUN docker-php-ext-configure zip && docker-php-ext-install zip

RUN docker-php-ext-configure intl && docker-php-ext-install intl

RUN docker-php-ext-configure pcntl --enable-pcntl && docker-php-ext-install pcntl

RUN docker-php-ext-configure gd && docker-php-ext-install gd

RUN pecl install -o -f redis &&  rm -rf /tmp/pear &&  docker-php-ext-enable redis

RUN curl https://getcomposer.org/download/2.6.6/composer.phar -o /usr/local/bin/composer && chmod +x /usr/local/bin/composer

RUN if [ "$(uname -m)" = "x86_64" ]; then curl -o prince.deb https://www.princexml.com/download/prince_15.4.1-1_debian11_amd64.deb; fi
RUN if [ "$(uname -m)" = "aarch64" ]; then curl -o prince.deb https://www.princexml.com/download/prince_15.4.1-1_debian11_arm64.deb; fi
RUN if [ -f prince.deb ]; then dpkg -i prince.deb; fi

RUN if [ "$(uname -m)" = "x86_64" ]; then curl -o prince.deb https://www.princexml.com/download/prince_16-1_debian11_amd64.deb; fi
RUN if [ "$(uname -m)" = "aarch64" ]; then curl -o prince.deb https://www.princexml.com/download/prince_16-1_debian11_arm64.deb; fi
RUN if [ -f prince.deb ]; then gdebi --non-interactive prince.deb; fi

RUN echo "memory_limit=4096M" > $PHP_INI_DIR/conf.d/memory-limit.ini
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" > $PHP_INI_DIR/conf.d/error_reporting.ini

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

RUN if [ "$ENABLE_XDEBUG" = "true" ] ; then \
      pecl install xdebug-3.1.5 && \
      docker-php-ext-enable xdebug ; \
  else \
      rm /usr/local/etc/php/conf.d/xdebug.ini ; \
  fi
