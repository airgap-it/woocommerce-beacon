FROM php:7.4-cli

WORKDIR /app

RUN apt-get update && apt-get install zip unzip

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
RUN alias composer='php /usr/bin/composer'
RUN export COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install
COPY . .
