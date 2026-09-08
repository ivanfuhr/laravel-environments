FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
