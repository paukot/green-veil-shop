# syntax=docker/dockerfile:1

############################################
# 1) PHP dependencies + application code
############################################
FROM serversideup/php:8.5-frankenphp-alpine AS vendor

USER root
# intl            -> required by the app
# pdo_mysql       -> MySQL driver
# redis           -> Redis driver (phpredis)
# pdo_sqlite      -> SQLite driver (only installed if the base image does not already ship it)
# sqlite, mariadb-client, redis -> CLI tools for debugging, database dumps, and cache checks
RUN install-php-extensions intl pdo_mysql redis \
    && (php -m | grep -qi '^pdo_sqlite$' || install-php-extensions pdo_sqlite) \
    && apk add --no-cache sqlite mariadb-client redis
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

USER www-data
WORKDIR /var/www/html

# Dependencies first: this layer is reused until composer.json / composer.lock change.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Application code, then generate the optimized autoloader (also runs package:discover etc.).
# There is no .env and no database at build time: DB_CONNECTION=sqlite and CACHE_STORE=array keep any
# package discovery or cache clears from trying to reach external services.
COPY --chown=www-data:www-data . .
RUN DB_CONNECTION=sqlite CACHE_STORE=array SESSION_DRIVER=array composer dump-autoload --no-dev --optimize --no-interaction

############################################
# 2) Frontend build (needs PHP + vendor/ for Vite plugins and Filament theme CSS)
############################################
FROM vendor AS assets

USER root
RUN apk add --no-cache nodejs npm

# The .env file is NOT part of the image, so VITE_* values must be passed as build args.
ARG VITE_APP_NAME=Laravel
ENV VITE_APP_NAME=$VITE_APP_NAME

RUN npm ci
RUN DB_CONNECTION=sqlite CACHE_STORE=array SESSION_DRIVER=array NODE_OPTIONS=--max-old-space-size=512 npm run build

############################################
# 3) Final image
############################################
FROM vendor AS production

# Startup scripts: run in alphabetical order before the web server starts.
USER root
COPY docker/entrypoint.d/ /etc/entrypoint.d/
RUN chmod -R 755 /etc/entrypoint.d/
USER www-data

COPY --from=assets --chown=www-data:www-data /var/www/html/public/build ./public/build

# Pre-build Filament's component + icon caches into the image (optional optimization, never fatal).
RUN DB_CONNECTION=sqlite CACHE_STORE=array SESSION_DRIVER=array php artisan filament:optimize || echo "filament:optimize skipped"
