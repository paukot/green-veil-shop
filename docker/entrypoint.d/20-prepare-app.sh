#!/bin/sh
# Runs on every container start, BEFORE the image's Laravel automations (50-laravel-automations.sh).
# The serversideup entrypoint SOURCES this file: never use `exit` in here.

_app_dir="${APP_BASE_DIR:-/var/www/html}"

# A fresh named volume mounted over ./storage is empty: recreate what Laravel needs.
mkdir -p \
    "$_app_dir/storage/app/public" \
    "$_app_dir/storage/framework/cache/data" \
    "$_app_dir/storage/framework/sessions" \
    "$_app_dir/storage/framework/views" \
    "$_app_dir/storage/logs" \
    "$_app_dir/storage/database"

# `php artisan migrate --force` does NOT create a missing SQLite file, so create it up front.
if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    _db_file="${DB_DATABASE:-$_app_dir/storage/database/database.sqlite}"
    mkdir -p "$(dirname "$_db_file")"
    if [ ! -e "$_db_file" ]; then
        echo "Creating SQLite database file: $_db_file"
        : > "$_db_file"
    fi
fi

unset _app_dir _db_file
