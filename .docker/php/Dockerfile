FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    libpq-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        gd \
        zip \
        opcache \
        mbstring \
        xml \
        bcmath \
        sockets \
        exif \
    && pecl install apcu \
    && docker-php-ext-enable apcu opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /srv/sylius

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY .docker/php/php.ini $PHP_INI_DIR/conf.d/app.ini
