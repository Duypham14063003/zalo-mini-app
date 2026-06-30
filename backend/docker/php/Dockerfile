FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libpq-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
