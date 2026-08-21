# --------------------------------------------------
# Base: PHP-FPM + extensions + PrinceXML
# --------------------------------------------------
FROM php:8.1-fpm AS base

ARG TARGETARCH

# Combine system utilities, PHP extension dependencies, and PrinceXML requirements
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libtidy-dev \
        zlib1g-dev \
        libpng-dev \
        libzip-dev \
        fontconfig-config \
        fonts-dejavu-core \
        libfontconfig1 \
        libfreetype6 \
        # Prince sub-dependencies
        libaom3 \
        libavif16 \
        libgif7 \
        libjpeg62-turbo \
        liblcms2-2 \
        libtiff6 \
        libwebp7 \
        libwebpdemux2 \
        gdebi-core \
        curl \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        bcmath \
        tidy \
        sockets \
        zip \
        intl \
        pcntl \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && curl -o /tmp/prince.deb "https://www.princexml.com/download/prince_16.2-1_debian13_${TARGETARCH}.deb" \
    # Using gdebi guarantees any missed underlying shared objects are fetched automatically
    && gdebi --non-interactive /tmp/prince.deb \
    # Cleanup of all caches and temporary binaries
    && rm -rf /tmp/pear /tmp/*.deb /var/lib/apt/lists/* \
    && apt-get clean

RUN echo "memory_limit=4096M" > "$PHP_INI_DIR/conf.d/memory-limit.ini" \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" > "$PHP_INI_DIR/conf.d/error_reporting.ini"

EXPOSE 9000

# --------------------------------------------------
# Builder: install deps and build app
# --------------------------------------------------
FROM base AS builder

COPY --from=composer:2.6.6 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --ignore-platform-reqs \
    --prefer-dist

COPY . .

# Ensure the log folder is created inside the source tree before copying to production
RUN mkdir -p log && composer dump-autoload \
    --optimize \
    --classmap-authoritative

# --------------------------------------------------
# Production: base + built app, no composer
# --------------------------------------------------
FROM base

WORKDIR /var/www/html

COPY --from=builder --chown=www-data:www-data /var/www/html .

USER www-data