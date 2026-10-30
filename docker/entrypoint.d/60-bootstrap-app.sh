#!/bin/sh
# Runs on every container start, AFTER migrations (50-laravel-automations.sh).
# The serversideup entrypoint SOURCES this file: never use `exit` in here.

_app_dir="${APP_BASE_DIR:-/var/www/html}"

if [ "${AUTORUN_ENABLED:-false}" = "true" ] && [ -f "$_app_dir/artisan" ]; then
    php "$_app_dir/artisan" app:bootstrap || echo "WARNING: 'php artisan app:bootstrap' failed (continuing)"
fi

unset _app_dir
