FROM composer:latest as composer
FROM php:7.4-cli-alpine

ARG USER_ID
ARG GROUP_ID

# Instll extensions
RUN apk update && apk upgrade
RUN apk add --no-cache php7-pear php7-dev gcc musl-dev make
RUN pecl install ast \
    && docker-php-ext-enable ast

# Install GIT so composer can download stuff
RUN apk add --no-cache git

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Fix permissions
RUN mkdir /.composer /.cache
RUN chown -R ${USER_ID}:${GROUP_ID} \
    /tmp \
    /.composer \
    /.cache
