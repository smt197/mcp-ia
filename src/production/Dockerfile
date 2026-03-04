ARG SERVERSIDEUP_PHP_VERSION=8.3-fpm-nginx-alpine
ARG USER_ID=9999
ARG GROUP_ID=9999

# =================================================================
# Stage 1: Composer dependencies
# =================================================================
FROM serversideup/php:${SERVERSIDEUP_PHP_VERSION} AS base

USER root

ARG USER_ID
ARG GROUP_ID

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

WORKDIR /var/www/html
COPY --chown=www-data:www-data composer.json ./

USER www-data
RUN --mount=type=cache,target=/tmp/cache \
    COMPOSER_CACHE_DIR=/tmp/cache composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader \
    --no-progress \
    --ignore-platform-reqs

# =================================================================
# Stage 2: Production image
# =================================================================
FROM serversideup/php:${SERVERSIDEUP_PHP_VERSION}

ARG USER_ID
ARG GROUP_ID

WORKDIR /var/www/html

USER root

RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    bcmath \
    gd \
    redis \
    opcache \
    pcntl \
    sockets

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

COPY src/production/etc/php/conf.d/zzz-custom-php.ini /usr/local/etc/php/conf.d/zzz-custom-php.ini
ENV PHP_OPCACHE_ENABLE=1

COPY --from=base --chown=www-data:www-data /var/www/html/vendor ./vendor

COPY --chown=www-data:www-data . .

RUN rm -f bootstrap/cache/*.php && \
    rm -rf storage/framework/views/* && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

COPY src/production/etc/nginx/conf.d/custom.conf /etc/nginx/conf.d/custom.conf
COPY src/production/etc/nginx/site-opts.d/http.conf /etc/nginx/site-opts.d/http.conf

USER www-data
