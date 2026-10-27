# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 - Composer dependencies
# ---------------------------------------------------------------------------
FROM php:8.3-cli-alpine AS vendor

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl zip bcmath gd pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --no-interaction \
      --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ---------------------------------------------------------------------------
# Stage 2 - Runtime: nginx + PHP-FPM under supervisord
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS runtime

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
      bcmath \
      exif \
      gd \
      intl \
      opcache \
      pcntl \
      pdo_mysql \
      zip \
  && apk add --no-cache nginx supervisor gettext tzdata mysql-client \
  && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

COPY docker/php.ini            /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf       /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf         /etc/nginx/nginx.conf
COPY docker/default.conf.tmpl  /etc/nginx/templates/default.conf.tmpl
COPY docker/supervisord.conf   /etc/supervisord.conf
COPY docker/entrypoint.sh      /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY --from=vendor --chown=www-data:www-data /app /var/www/html

# The base image leaves /var/www/html world-writable and sticky (1777).
# With fs.protected_symlinks on, the kernel then refuses to let www-data
# follow a root-owned symlink inside it, which is exactly what the storage
# and uploads symlinks are. Tightening the mode is the fix.
RUN chmod +x /var/www/html/craft \
  && chmod 0755 /var/www/html \
  && mkdir -p /var/lib/nginx/tmp /var/log/supervisor \
  && chown -R www-data:www-data /var/www/html /var/lib/nginx

ENV PORT=8080 \
    PERSIST_DIR=/mnt/persist \
    CRAFT_ENVIRONMENT=production

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
