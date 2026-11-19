FROM php:8.5-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf

# Composer runs as the host user (see docker-compose.yml), so its home must be writable for any uid.
ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www

CMD ["php-fpm"]
