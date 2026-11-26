#!/usr/bin/env bash
#
# Server-side half of deploy.sh. Runs inside the git checkout on the server
# (deploy.sh has already moved it to the latest commit of the branch).
#
#   remote-deploy.sh deploy [--app-url URL] [--public-port N]
#                                     back up, build, restart (migrations run on start)
#   remote-deploy.sh backup           MySQL dump + uploaded images into backups/
#   remote-deploy.sh restore FILE     restore backups/db-*.sql.gz or backups/files-*.tar.gz
#   remote-deploy.sh status           docker compose ps
#   remote-deploy.sh logs             follow container logs
#
# The data (MySQL, Redis and uploaded images) lives in Docker volumes
# (`mysql_data`, `redis_data`, `storage`). A deploy never touches them: it only
# replaces containers and images. Never run `docker compose down -v` or
# `docker volume rm` here.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

KEEP=${KEEP:-30}

die() {
    printf 'remote-deploy: %s\n' "$*" >&2
    exit 1
}

info() {
    printf '  -> %s\n' "$*"
}

mode=${1:-deploy}
[[ $# -eq 0 ]] || shift

app_url=
public_port=
restore_file=
if [[ $mode == restore ]]; then
    restore_file=${1:?restore needs a backups/ file}
    shift
fi
while (($#)); do
    case $1 in
        --app-url)
            app_url=${2:?--app-url needs a value}
            shift
            ;;
        --public-port)
            public_port=${2:?--public-port needs a value}
            shift
            ;;
        *) die "unknown argument: $1" ;;
    esac
    shift
done

[[ -z $app_url || $app_url =~ ^https?://[A-Za-z0-9._:/-]+$ ]] || die "invalid --app-url: $app_url"
[[ -z $public_port || $public_port =~ ^[0-9]{1,5}$ ]] || die "invalid --public-port: $public_port"

COMPOSE=(docker compose)

require_docker() {
    docker compose version >/dev/null 2>&1 \
        || die 'Docker Compose v2 is required and this user must be able to run docker (docker group).'
}

take_lock() {
    command -v flock >/dev/null 2>&1 || die 'flock (util-linux) is required'
    exec 9>.deploy.lock
    flock -n 9 || die 'another deploy is already running'
}

ensure_env() {
    [[ -f .env ]] && return 0
    [[ -f .env.docker.example ]] || die '.env.docker.example is missing from the deployed tree'
    info 'Creating .env from .env.docker.example with a fresh APP_KEY and database passwords'
    cp .env.docker.example .env
    sed -i \
        -e "s|^APP_KEY=\$|APP_KEY=base64:$(openssl rand -base64 32)|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 16)|" \
        -e "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -hex 16)|" \
        .env
    if [[ -n $app_url ]]; then
        sed -i "s|^APP_URL=.*|APP_URL=${app_url}|" .env
        local domain=${app_url#*://}
        domain=${domain%%[:/]*}
        sed -i "s|^APP_DOMAIN=.*|APP_DOMAIN=${domain}|" .env
    else
        info 'Warning: no DEPLOY_APP_URL given, APP_URL stays at its localhost default'
    fi
    chmod 600 .env
}

# An existing .env is never rewritten (it holds the APP_KEY). The one exception:
# an explicitly requested public port is enforced on every deploy.
apply_public_port() {
    [[ -n $public_port ]] || return 0
    if grep -q '^PUBLIC_PORT=' .env; then
        sed -i "s|^PUBLIC_PORT=.*|PUBLIC_PORT=${public_port}|" .env
    else
        printf 'PUBLIC_PORT=%s\n' "$public_port" >>.env
    fi
    info "Proxy will listen on port $public_port"
}

require_certs() {
    [[ -r certs/origin.pem && -r certs/origin.key ]] \
        || die 'certs/origin.pem and certs/origin.key are missing. Upload the Cloudflare Origin Certificate once with: ./deploy.sh cert <origin.pem> <origin.key>'
}

app_running() {
    [[ -n $("${COMPOSE[@]}" ps --status running -q app 2>/dev/null) ]]
}

rotate() {
    local pattern=$1
    # shellcheck disable=SC2012,SC2086
    ls -1t $pattern 2>/dev/null | tail -n +"$((KEEP + 1))" | xargs -r rm --
}

# Consistent while the shop is running (--single-transaction snapshot, no table locks).
backup() {
    local label=${1:-manual} stamp db files
    app_running || die 'the app container is not running, nothing to back up (start the stack first)'
    mkdir -p backups
    stamp=$(date +%Y%m%d-%H%M%S)
    db="backups/db-$stamp-$label.sql.gz"
    files="backups/files-$stamp-$label.tar.gz"

    if ! "${COMPOSE[@]}" exec -T mysql sh -c \
        'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --no-tablespaces "$MYSQL_DATABASE"' \
        2>/dev/null | gzip >"$db"; then
        rm -f "$db"
        die 'database backup failed'
    fi
    gzip -t "$db"
    # A dump without any table means an empty or wrong database: keep it, but say so.
    if ! gunzip -c "$db" | grep -q 'CREATE TABLE'; then
        info "Warning: $db contains no tables (empty database?)"
    fi
    info "Database backup: $db"

    if "${COMPOSE[@]}" exec -T app sh -c 'cd /var/www/html/storage/app && [ -d public ] && tar -czf - public' >"$files" 2>/dev/null \
        && gzip -t "$files" && [[ -s $files ]]; then
        info "Uploaded images backup: $files"
    else
        rm -f "$files"
        info 'No uploaded images to back up'
    fi

    rotate 'backups/db-*.sql.gz'
    rotate 'backups/files-*.tar.gz'
}

restore() {
    local file=$restore_file ok=1
    [[ $file =~ ^backups/[A-Za-z0-9._-]+\.(sql\.gz|tar\.gz)$ ]] || die "not a backup file: $file"
    [[ -r $file ]] || die "no such file: $file"
    gzip -t "$file" || die "$file is corrupt"

    info 'Taking a safety backup of the current data first'
    if app_running; then
        backup pre-restore
    fi

    info 'Stopping the shop (proxy, app, worker, scheduler)'
    "${COMPOSE[@]}" stop proxy scheduler worker app

    if [[ $file == *.sql.gz ]]; then
        info "Restoring the database from $file"
        "${COMPOSE[@]}" up -d --wait mysql
        # mysqldump output starts with the table drops/creates, so it replaces the current tables.
        gunzip -c "$file" | "${COMPOSE[@]}" exec -T mysql sh -c \
            'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' || ok=0
    else
        info "Restoring the uploaded images from $file"
        "${COMPOSE[@]}" run --rm -T --no-deps --entrypoint sh app -c '
            set -e
            mkdir -p /var/www/html/storage/app
            tar -xzf - -C /var/www/html/storage/app
        ' <"$file" || ok=0
    fi

    if ((!ok)); then
        "${COMPOSE[@]}" up -d --wait || true
        die 'restore failed, the stack was started again (the safety backup taken above is in backups/)'
    fi

    info 'Starting the stack'
    "${COMPOSE[@]}" up -d --wait
    info 'Restored'
}

dump_diagnostics() {
    "${COMPOSE[@]}" ps || true
    "${COMPOSE[@]}" logs --tail=100 || true
}

# The real proof that the public path works: TLS on the proxy answering /up.
smoke_test() {
    local port
    port=$(grep -E '^PUBLIC_PORT=' .env | tail -1 | cut -d= -f2 || true)
    port=${port:-8443}
    if ! command -v curl >/dev/null 2>&1; then
        info 'curl is not installed, skipping the final HTTPS check'
        return 0
    fi
    local code
    code=$(curl -ksS -o /dev/null -w '%{http_code}' --max-time 15 "https://127.0.0.1:${port}/up" || true)
    [[ $code == 200 ]] || die "the proxy did not answer /up with 200 on port ${port} (got: ${code:-no response})"
    info "HTTPS check passed on port ${port}"
}

deploy() {
    require_docker
    take_lock
    require_certs
    ensure_env
    apply_public_port

    if app_running; then
        info 'Backing up the data before touching anything'
        backup pre-deploy
    else
        info 'No running stack yet, skipping the pre-deploy backup'
    fi

    info 'Building images'
    "${COMPOSE[@]}" build --pull

    # Migrations and storage:link run in the app container's entrypoint, so --wait
    # only returns once they have finished. The worker and scheduler start after it.
    info 'Starting the stack'
    if ! "${COMPOSE[@]}" up -d --wait --remove-orphans; then
        dump_diagnostics
        die 'the stack did not become healthy (see above). Data is untouched; the last backup is in backups/ (restore with ./deploy.sh restore backups/FILE)'
    fi

    "${COMPOSE[@]}" ps
    smoke_test
    docker image prune -f >/dev/null
    info 'Done'
}

case $mode in
    deploy) deploy ;;
    backup)
        require_docker
        take_lock
        backup manual
        ;;
    restore)
        require_docker
        take_lock
        restore
        ;;
    status)
        require_docker
        "${COMPOSE[@]}" ps
        ;;
    logs)
        require_docker
        "${COMPOSE[@]}" logs -f --tail=100
        ;;
    *) die "unknown mode: $mode" ;;
esac
