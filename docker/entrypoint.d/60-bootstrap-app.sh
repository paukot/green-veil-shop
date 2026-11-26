#!/bin/sh
# Runs on every container start, AFTER migrations (50-laravel-automations.sh).
# The serversideup entrypoint SOURCES this file: never use `exit` in here.

_app_dir="${APP_BASE_DIR:-/var/www/html}"

# `app:bootstrap` is optional: only run it when the application defines it.
if [ "${AUTORUN_ENABLED:-false}" = "true" ] && [ -f "$_app_dir/artisan" ] \
    && php "$_app_dir/artisan" list --raw 2>/dev/null | grep -q '^app:bootstrap'; then
    php "$_app_dir/artisan" app:bootstrap || echo "WARNING: 'php artisan app:bootstrap' failed (continuing)"
fi

unset _app_dir
