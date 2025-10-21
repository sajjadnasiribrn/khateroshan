FROM php:8.3-fpm-alpine

ARG UID
ARG GID

ENV UID=${UID:-1000}
ENV GID=${GID:-1000}
ENV COMPOSER_HOME=/home/laravel/.composer

RUN mkdir -p /var/www/html
WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN addgroup -g ${GID} --system laravel \
    && adduser -G laravel --system -D -s /bin/sh -u ${UID} laravel

RUN sed -i -e "s/upload_max_filesize = .*/upload_max_filesize = 1G/g" \
    -e "s/post_max_size = .*/post_max_size = 1G/g" \
    -e "s/memory_limit = .*/memory_limit = 2048M/g" \
    /usr/local/etc/php/php.ini-production \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

RUN apk update && apk add --no-cache \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    mysql-client \
    libzip-dev \
    gmp-dev \
    icu-dev \
    curl \
    bash

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo pdo_mysql pcntl intl exif zip gmp bcmath \
    && docker-php-ext-enable intl opcache zip

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz \
    | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

COPY --chown=laravel:laravel ./start.sh /usr/local/bin/start
COPY --chown=laravel:laravel ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN mkdir -p /var/log/supervisor \
    && chown -R laravel:laravel /var/log/supervisor \
    && chmod +x /usr/local/bin/start

RUN mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache \
    && chown -R laravel:laravel /var/www/html

RUN sed -i "s/^user = .*/user = laravel/g" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/^group = .*/group = laravel/g" /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

USER laravel
WORKDIR /var/www/html

COPY --chown=laravel:laravel ./src/composer.json /var/www/html/composer.json
COPY --chown=laravel:laravel ./src/composer.lock /var/www/html/composer.lock

RUN composer install --no-interaction --no-scripts --no-autoloader --ignore-platform-reqs \
    && rm -rf "$COMPOSER_HOME"

COPY --chown=laravel:laravel ./src/ /var/www/html/

RUN mkdir -p storage/logs bootstrap/cache \
    && chown -R laravel:laravel storage bootstrap/cache

CMD ["/usr/local/bin/start"]