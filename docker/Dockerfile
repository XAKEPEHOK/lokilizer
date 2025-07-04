#syntax=docker/dockerfile:1.4

### PHP ###
ARG PHP_VERSION="8.2"
ARG PHP_ALPINE_VERSION="3.17"

FROM composer/composer:2-bin AS composer

FROM php:${PHP_VERSION}-fpm-alpine${PHP_ALPINE_VERSION} AS php

ARG LOKILIZER_DOCKER_UID
ARG LOKILIZER_DOCKER_GID

ARG REDIS_VERSION="6.0.2"
ARG MONGODB_VERSION="1.19.3"

ENV LOKILIZER_DOCKER_UID=${LOKILIZER_DOCKER_UID}
ENV LOKILIZER_DOCKER_GID=${LOKILIZER_DOCKER_GID}
ENV LOKILIZER_USER_NAME="lokilizer"

RUN set -x \
    # install permanent dependencies
    && apk add --no-cache linux-headers \
        icu-dev \
        tidyhtml-dev \
        openssl-dev \
        git \
    # install build-time dependencies
    && apk add --no-cache --virtual .build-deps \
        autoconf \
        openssl \
        make \
        g++ \
    # install PHP extensions (CFLAGS usage reason - https://bit.ly/3ALS5NU) \
    && docker-php-ext-install -j$(nproc) \
        intl \
        pcntl \
        1>/dev/null \
    && pecl install redis-${REDIS_VERSION} mongodb-${MONGODB_VERSION} timezonedb \
    && docker-php-ext-enable redis mongodb pcntl intl timezonedb \
    # make clean up
    && docker-php-source delete \
    && apk del .build-deps \
    && rm -R /tmp/pear \
    # create unprivileged user
    && adduser \
        --disabled-password \
        --shell "/sbin/nologin" \
        --home "/nonexistent" \
        --no-create-home \
        --uid $LOKILIZER_DOCKER_UID \
        --gecos "" \
        $LOKILIZER_USER_NAME

COPY --link docker/php/www.conf $PHP_INI_DIR/../php-fpm.d/

RUN sed -i "s/__APP_USER_NAME/$LOKILIZER_DOCKER_UID/" $PHP_INI_DIR/../php-fpm.d/* \
    && sed -i "s/__APP_GROUP_NAME/$LOKILIZER_DOCKER_GID/" $PHP_INI_DIR/../php-fpm.d/*


WORKDIR /app

COPY --from=composer --link /composer /usr/bin/composer
ENV COMPOSER_HOME="/tmp/composer"
# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

FROM php AS php_prod
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

FROM php AS php_dev

ARG XDEBUG_VERSION="3.2.0"

RUN set -eux; \
    mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini \
        && apk add --no-cache --virtual .build-deps \
                --update linux-headers \
                autoconf \
                openssl \
                make \
                g++ \
                ca-certificates \
        && pecl install xdebug-${XDEBUG_VERSION} \
        && docker-php-ext-enable xdebug \
        && mkdir -p /opt \
        && chown $LOKILIZER_USER_NAME:$LOKILIZER_USER_NAME /usr/bin/git \
        && chown $LOKILIZER_USER_NAME:$LOKILIZER_USER_NAME /opt \
        && apk del .build-deps

COPY --link docker/php/dev/conf.d/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM php_prod AS php_fpm_prod

COPY --link docker/php/php-fpm.ini $PHP_INI_DIR/conf.d/
COPY docker/php/php-fpm-entrypoint.sh /usr/local/bin/php-fpm-entrypoint.sh
RUN chmod +x /usr/local/bin/php-fpm-entrypoint.sh
USER $LOKILIZER_USER_NAME

FROM php_dev AS php_fpm_dev

COPY --link docker/php/php-fpm.ini $PHP_INI_DIR/conf.d/
USER $LOKILIZER_USER_NAME

FROM php_prod AS php_cli_prod

COPY --link docker/php/php-cli.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/prod/cli-docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

USER $LOKILIZER_USER_NAME
ENTRYPOINT ["docker-entrypoint"]
CMD ["php", "-a"]

FROM php_prod AS php_cli_cron

ARG CRONTAB_FILENAME

RUN apk add --update supervisor && \
    apk add --no-cache supervisor && \
    mkdir -p /logs

COPY --link docker/php/php-cli.ini $PHP_INI_DIR/conf.d/
COPY --link docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# cron settings
COPY --link ${CRONTAB_FILENAME} /lokilizer_crontab
RUN chown $LOKILIZER_USER_NAME:$LOKILIZER_USER_NAME /lokilizer_crontab
RUN crontab -u $LOKILIZER_USER_NAME /lokilizer_crontab
COPY --link docker/php/prod/cron-docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]

FROM php_dev AS php_cli_dev

COPY --link docker/php/php-cli.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/dev/cli-docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

USER $LOKILIZER_USER_NAME
ENTRYPOINT ["docker-entrypoint"]
CMD ["php", "-a"]

### REDIS ###
FROM redis:6.2-alpine3.17 AS redis

ARG LOKILIZER_DOCKER_UID
ARG LOKILIZER_DOCKER_GID

RUN set -x \
    && echo "http://dl-2.alpinelinux.org/alpine/edge/community/" >> /etc/apk/repositories \
    && apk --no-cache add shadow \
    && usermod --uid ${LOKILIZER_DOCKER_UID} redis \
    && groupmod --gid ${LOKILIZER_DOCKER_GID} redis \
    && apk del shadow

### TRAEFIK ###
FROM traefik:v2.6 AS traefik

### NGINX ###
FROM nginx:1.23-alpine AS nginx

ARG LOKILIZER_DOCKER_UID
ARG LOKILIZER_DOCKER_GID

RUN set -x \
    && echo "http://dl-2.alpinelinux.org/alpine/edge/community/" >> /etc/apk/repositories \
    && apk --no-cache add shadow \
    && usermod --uid ${LOKILIZER_DOCKER_UID} nginx \
    && groupmod --gid ${LOKILIZER_DOCKER_GID} nginx \
    && apk del shadow \
    && mkdir -p /app \
    && chown nginx:nginx /app

WORKDIR /app

### MONGO ###
FROM mongo:6-focal AS mongo

ARG LOKILIZER_DOCKER_UID
ARG LOKILIZER_DOCKER_GID

RUN usermod --uid ${LOKILIZER_DOCKER_UID} mongodb \
&& groupmod --gid ${LOKILIZER_DOCKER_GID} mongodb \
&& mkdir -p /etc/key \
&& chown mongodb:mongodb /etc/key \
&& mkdir -p /var/log/mongo \
&& chown mongodb:root -R /var/log/mongo \
&& chown mongodb:mongodb -R /data