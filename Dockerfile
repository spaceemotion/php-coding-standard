FROM composer:latest as composer
FROM php:8.1-cli-alpine

ARG USER_ID
ARG GROUP_ID

# Add php extensions
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions ast

# Install GIT so composer can download stuff
RUN apk add --no-cache git

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Fix permissions
RUN mkdir /.composer /.cache
RUN chown -R ${USER_ID}:${GROUP_ID} \
    /tmp \
    /.composer \
    /.cache
