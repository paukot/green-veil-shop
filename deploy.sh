#!/usr/bin/env bash
#
# Green Veil cosmetic shop one-command deploy to a self-hosted (KVM) server.
#
# It connects to the server over SSH, updates the git checkout there (fetch +
# reset to the remote branch), then runs docker/remote-deploy.sh, which backs up
# the data, builds the images on the server and restarts the stack (migrations
# run when the app container starts). Nothing is uploaded: push your commits
# first. The same script runs from a workstation and from CI.
#
# Usage:
#   ./deploy.sh [deploy] [--dry-run]            update the checkout, back up, build, restart
#   ./deploy.sh cert <origin.pem> <origin.key>  upload the Cloudflare Origin Certificate (once)
#   ./deploy.sh backup                          back up the data on the server now
#   ./deploy.sh pull-backups                    copy the server's backups/ to ./backups/
#   ./deploy.sh restore <backups/FILE>          restore a backup made on the server
#   ./deploy.sh status                          docker compose ps on the server
#   ./deploy.sh logs                            follow the server's container logs
#
# Configuration (environment variables, or a git-ignored .deploy.env file; the
# environment wins). KVM hosts rarely expose SSH on port 22, so set the port:
#   DEPLOY_HOST            server address (required); user@host is accepted too
#   DEPLOY_SSH_PORT        SSH port, default 22
#   DEPLOY_USER            SSH user, default deploy (must be able to run docker)
#   DEPLOY_PATH            the git checkout on the server, default /var/www/green-veil-cosmetic-shop
#   DEPLOY_BRANCH          branch to deploy (default: the branch the server checkout is on)
#   DEPLOY_REPO_URL        only for a brand new server: clone URL used if DEPLOY_PATH has no checkout yet
#   DEPLOY_SSH_KEY         private key file (optional, else ssh's defaults/agent)
#   DEPLOY_KNOWN_HOSTS     known_hosts file (optional, else ~/.ssh/known_hosts)
#   DEPLOY_HOST_KEY_CHECKING  ssh StrictHostKeyChecking value, default yes
#   DEPLOY_APP_URL         public URL, written to .env on its first creation
#   DEPLOY_PUBLIC_PORT     port the Caddy proxy publishes on the server (PUBLIC_PORT in
#                          .env, default 8443). Applied on every deploy when set.
#                          Use a port your host exposes; with a Cloudflare Origin Rule
#                          rewriting the destination port it can be any port.
#
# First time: `ssh-keyscan -p <port> <host> >> ~/.ssh/known_hosts`, then
# `./deploy.sh cert origin.pem origin.key`, then `./deploy.sh`.

set -euo pipefail

if ((BASH_VERSINFO[0] < 4 || (BASH_VERSINFO[0] == 4 && BASH_VERSINFO[1] < 4))); then
    printf 'deploy: bash 4.4 or newer is required (found %s)\n' "$BASH_VERSION" >&2
    exit 1
fi

cd "$(dirname "${BASH_SOURCE[0]}")"

die() {
    printf 'deploy: %s\n' "$*" >&2
    exit 1
}

info() {
    printf '==> %s\n' "$*"
}

usage() {
    sed -n '3,/^$/{s/^# \{0,1\}//;p}' "${BASH_SOURCE[0]}" | sed '$d'
    sed -n '/^# Usage:/,/^# First time/{s/^# \{0,1\}//;p}' "${BASH_SOURCE[0]}"
}

# Reads DEPLOY_* assignments from a file without executing it. A value already
# present in the environment is never overridden.
load_config_file() {
    local file=$1 line key value number=0
    [[ -f $file ]] || return 0

    while IFS= read -r line || [[ -n $line ]]; do
        number=$((number + 1))
        [[ $line =~ ^[[:space:]]*(#|$) ]] && continue
        [[ $line =~ ^(DEPLOY_[A-Z_]+)=(.*)$ ]] || die "$file:$number: expected DEPLOY_NAME=value"
        key=${BASH_REMATCH[1]}
        value=${BASH_REMATCH[2]}
        value=${value#\"}
        value=${value%\"}
        value=${value#\'}
        value=${value%\'}
        if [[ -z ${!key:-} ]]; then
            printf -v "$key" '%s' "$value"
        fi
    done <"$file"
}

# ---------------------------------------------------------------------------
# Arguments
# ---------------------------------------------------------------------------
dry_run=0
positional=()

while (($#)); do
    case $1 in
        --dry-run) dry_run=1 ;;
        -h | --help)
            usage
            exit 0
            ;;
        --)
            shift
            positional+=("$@")
            break
            ;;
        -*) die "unknown option: $1 (see --help)" ;;
        *) positional+=("$1") ;;
    esac
    shift
done

command_name=${positional[0]:-deploy}

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
load_config_file .deploy.env

DEPLOY_HOST=${DEPLOY_HOST:-}

# Accept the familiar user@host form too and split it into its two parts.
if [[ $DEPLOY_HOST == *@* ]]; then
    host_user=${DEPLOY_HOST%@*}
    DEPLOY_HOST=${DEPLOY_HOST##*@}
    if [[ -n ${DEPLOY_USER:-} && $DEPLOY_USER != "$host_user" ]]; then
        die "DEPLOY_HOST says user '$host_user' but DEPLOY_USER is '$DEPLOY_USER'. Set only one of them."
    fi
    DEPLOY_USER=$host_user
fi

DEPLOY_USER=${DEPLOY_USER:-deploy}
DEPLOY_SSH_PORT=${DEPLOY_SSH_PORT:-22}
DEPLOY_PATH=${DEPLOY_PATH:-/var/www/green-veil-cosmetic-shop}
DEPLOY_BRANCH=${DEPLOY_BRANCH:-}
DEPLOY_REPO_URL=${DEPLOY_REPO_URL:-}
DEPLOY_SSH_KEY=${DEPLOY_SSH_KEY:-}
DEPLOY_KNOWN_HOSTS=${DEPLOY_KNOWN_HOSTS:-}
DEPLOY_HOST_KEY_CHECKING=${DEPLOY_HOST_KEY_CHECKING:-yes}
DEPLOY_APP_URL=${DEPLOY_APP_URL:-}
DEPLOY_PUBLIC_PORT=${DEPLOY_PUBLIC_PORT:-}

[[ -n $DEPLOY_HOST ]] || die 'DEPLOY_HOST is not set (export it or put it in .deploy.env, see .deploy.env.example)'
[[ $DEPLOY_HOST =~ ^[A-Za-z0-9._:-]+$ ]] || die "DEPLOY_HOST contains unsupported characters: $DEPLOY_HOST"
[[ $DEPLOY_USER =~ ^[a-z_][a-z0-9_-]*$ ]] || die "DEPLOY_USER is not a valid user name: $DEPLOY_USER"
if ! [[ $DEPLOY_SSH_PORT =~ ^[0-9]{1,5}$ ]] || ((10#$DEPLOY_SSH_PORT < 1 || 10#$DEPLOY_SSH_PORT > 65535)); then
    die "DEPLOY_SSH_PORT must be a number from 1 to 65535 (got: $DEPLOY_SSH_PORT)"
fi
DEPLOY_SSH_PORT=$((10#$DEPLOY_SSH_PORT))
[[ $DEPLOY_PATH =~ ^/[A-Za-z0-9._/-]+$ && $DEPLOY_PATH != *..* ]] \
    || die "DEPLOY_PATH must be an absolute path of letters, digits and ._/- (got: $DEPLOY_PATH)"
[[ $DEPLOY_HOST_KEY_CHECKING =~ ^(yes|accept-new)$ ]] \
    || die 'DEPLOY_HOST_KEY_CHECKING must be "yes" or "accept-new"'
[[ -z $DEPLOY_BRANCH || ( $DEPLOY_BRANCH =~ ^[A-Za-z0-9][A-Za-z0-9._/-]*$ && $DEPLOY_BRANCH != *..* ) ]] \
    || die "DEPLOY_BRANCH is not a valid branch name: $DEPLOY_BRANCH"
[[ -z $DEPLOY_REPO_URL || $DEPLOY_REPO_URL =~ ^(https://|git@)[A-Za-z0-9._:/@-]+$ ]] \
    || die "DEPLOY_REPO_URL must be an https:// or git@ clone URL (got: $DEPLOY_REPO_URL)"
[[ -z $DEPLOY_APP_URL || $DEPLOY_APP_URL =~ ^https?://[A-Za-z0-9._:/-]+$ ]] \
    || die "DEPLOY_APP_URL must look like https://host[:port] (got: $DEPLOY_APP_URL)"
if [[ -n $DEPLOY_PUBLIC_PORT ]]; then
    if ! [[ $DEPLOY_PUBLIC_PORT =~ ^[0-9]{1,5}$ ]] || ((10#$DEPLOY_PUBLIC_PORT < 1 || 10#$DEPLOY_PUBLIC_PORT > 65535)); then
        die "DEPLOY_PUBLIC_PORT must be a number from 1 to 65535 (got: $DEPLOY_PUBLIC_PORT)"
    fi
    DEPLOY_PUBLIC_PORT=$((10#$DEPLOY_PUBLIC_PORT))
fi

# Keep the key and known_hosts paths plain (no spaces or shell characters).
for path_value in "$DEPLOY_SSH_KEY" "$DEPLOY_KNOWN_HOSTS"; do
    [[ -z $path_value || $path_value =~ ^[A-Za-z0-9._/~+-]+$ ]] \
        || die "DEPLOY_SSH_KEY / DEPLOY_KNOWN_HOSTS paths may only use letters, digits and ._/~+-: $path_value"
done
[[ -z $DEPLOY_SSH_KEY || -r $DEPLOY_SSH_KEY ]] || die "DEPLOY_SSH_KEY is not readable: $DEPLOY_SSH_KEY"
[[ -z $DEPLOY_KNOWN_HOSTS || -r $DEPLOY_KNOWN_HOSTS ]] || die "DEPLOY_KNOWN_HOSTS is not readable: $DEPLOY_KNOWN_HOSTS"

# One option array feeds every ssh call, so the custom SSH port can never be
# applied to one step and forgotten on another.
SSH_OPTS=(
    -p "$DEPLOY_SSH_PORT"
    -o BatchMode=yes
    -o ConnectTimeout=10
    -o ServerAliveInterval=15
    -o ServerAliveCountMax=4
    -o "StrictHostKeyChecking=$DEPLOY_HOST_KEY_CHECKING"
)
if [[ -n $DEPLOY_SSH_KEY ]]; then
    SSH_OPTS+=(-i "$DEPLOY_SSH_KEY" -o IdentitiesOnly=yes)
fi
if [[ -n $DEPLOY_KNOWN_HOSTS ]]; then
    SSH_OPTS+=(-o "UserKnownHostsFile=$DEPLOY_KNOWN_HOSTS")
fi

SSH_TARGET="$DEPLOY_USER@$DEPLOY_HOST"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

# Runs a command, or only prints it under --dry-run.
run() {
    if ((dry_run)); then
        printf '[dry-run]'
        printf ' %q' "$@"
        printf '\n'
    else
        "$@"
    fi
}

# Runs a command on the server inside DEPLOY_PATH. DEPLOY_PATH and every
# argument were validated above, so plain single quoting is safe.
remote() {
    local tty_flag=()
    if [[ ${1:-} == -t ]]; then
        tty_flag=(-t)
        shift
    fi
    run ssh "${tty_flag[@]}" "${SSH_OPTS[@]}" "$SSH_TARGET" "cd '$DEPLOY_PATH' && $*"
}

preflight() {
    info "Checking SSH access to $SSH_TARGET on port $DEPLOY_SSH_PORT"
    if ! run ssh "${SSH_OPTS[@]}" "$SSH_TARGET" true; then
        die "cannot reach $SSH_TARGET on port $DEPLOY_SSH_PORT. Check the port, the key, and that the host key is trusted (ssh-keyscan -p $DEPLOY_SSH_PORT $DEPLOY_HOST >> ~/.ssh/known_hosts)."
    fi
}

# The install path must already be a git checkout. On a brand new server it is
# cloned once, when DEPLOY_REPO_URL is set.
ensure_checkout() {
    local clone_cmd
    if [[ -n $DEPLOY_REPO_URL ]]; then
        clone_cmd="git clone '$DEPLOY_REPO_URL' '$DEPLOY_PATH'"
    else
        clone_cmd="echo 'deploy: $DEPLOY_PATH is not a git checkout. Clone the repository there once, or set DEPLOY_REPO_URL.' >&2; exit 1"
    fi
    run ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "[ -d '$DEPLOY_PATH/.git' ] || { $clone_cmd; }" \
        || die "cannot prepare $DEPLOY_PATH on the server (needs to be writable by $DEPLOY_USER)."
}

# Shell snippet that moves the server checkout to the tip of the remote branch.
# Local changes in the checkout are discarded: the server never holds work.
update_checkout_cmd() {
    local branch=$DEPLOY_BRANCH
    if [[ -z $branch ]]; then
        printf '%s' 'b=$(git rev-parse --abbrev-ref HEAD) && { [ "$b" != HEAD ] || b=master; } && '
        printf '%s' 'git fetch --prune origin "$b" && git checkout -q -f -B "$b" FETCH_HEAD'
    else
        printf "git fetch --prune origin '%s' && git checkout -q -f -B '%s' FETCH_HEAD" "$branch" "$branch"
    fi
}

# ---------------------------------------------------------------------------
# Commands
# ---------------------------------------------------------------------------
remote_args() {
    local args=$1
    if [[ -n $DEPLOY_APP_URL ]]; then
        args+=" --app-url '$DEPLOY_APP_URL'"
    fi
    if [[ -n $DEPLOY_PUBLIC_PORT ]]; then
        args+=" --public-port $DEPLOY_PUBLIC_PORT"
    fi
    printf '%s' "bash ./docker/remote-deploy.sh $args"
}

cmd_deploy() {
    preflight
    ensure_checkout
    info 'Updating the checkout, then building and restarting the stack on the server'
    remote "$(update_checkout_cmd) && git log -1 --format='  -> deploying %h %s' && $(remote_args deploy)"
    if ((dry_run)); then
        info 'Dry run finished, nothing was changed'
    else
        info "Deployed to $DEPLOY_HOST"
    fi
}

cmd_cert() {
    local cert=${positional[1]:-} key=${positional[2]:-}
    [[ -r $cert && -r $key ]] || die 'usage: ./deploy.sh cert <origin.pem> <origin.key>'
    preflight
    ensure_checkout
    info 'Uploading the Cloudflare Origin Certificate'
    run ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "cd '$DEPLOY_PATH' && umask 077 && mkdir -p certs && cat > certs/origin.pem" <"$cert"
    run ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "cd '$DEPLOY_PATH' && umask 077 && cat > certs/origin.key" <"$key"
    info 'Certificate installed. Run ./deploy.sh to start (or restart) the stack.'
}

cmd_backup() {
    preflight
    remote 'bash ./docker/remote-deploy.sh backup'
}

cmd_pull_backups() {
    preflight
    mkdir -p backups
    info 'Copying the server backups to ./backups/'
    if ((dry_run)); then
        printf '[dry-run] ssh ... tar -cf - backups | tar -xf - -C .\n'
        return 0
    fi
    ssh "${SSH_OPTS[@]}" "$SSH_TARGET" "cd '$DEPLOY_PATH' && tar -cf - backups" | tar -xf - -C .
}

cmd_restore() {
    local file=${positional[1]:-}
    [[ $file =~ ^backups/[A-Za-z0-9._-]+\.(sql\.gz|tar\.gz)$ ]] \
        || die 'usage: ./deploy.sh restore backups/<file>  (a file that exists in backups/ on the server, see ./deploy.sh backup)'
    preflight
    remote "bash ./docker/remote-deploy.sh restore '$file'"
}

cmd_status() {
    remote 'bash ./docker/remote-deploy.sh status'
}

cmd_logs() {
    remote -t 'bash ./docker/remote-deploy.sh logs'
}

case $command_name in
    deploy) cmd_deploy ;;
    cert) cmd_cert ;;
    backup) cmd_backup ;;
    pull-backups) cmd_pull_backups ;;
    restore) cmd_restore ;;
    status) cmd_status ;;
    logs) cmd_logs ;;
    *) die "unknown command: $command_name (see --help)" ;;
esac
