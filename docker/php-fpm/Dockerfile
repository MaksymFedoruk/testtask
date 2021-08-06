FROM php:7.4-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-3.0.4 \
    && docker-php-ext-enable xdebug

ADD ./php.ini /usr/local/etc/php/7.4/cli/php.ini

WORKDIR /var/www/html/