#!/usr/bin/env bash
# MySQL backup plus the uploaded images, safe while the site is running.
# Run on the server in the install directory (or from your workstation: ./deploy.sh backup).
# Files land in backups/ (db-*.sql.gz and files-*.tar.gz), the newest KEEP (default 30) are kept.
# Restore:  ./deploy.sh restore backups/db-XXXX.sql.gz   (or on the server: bash docker/remote-deploy.sh restore ...)
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
exec bash docker/remote-deploy.sh backup
