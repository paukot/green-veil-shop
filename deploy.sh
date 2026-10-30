#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

if [ ! -f .env ]; then
    echo "Missing .env - run: cp .env.docker.example .env and fill it in." >&2
    exit 1
fi

# Detect current branch or fallback to master
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "master")
if [ "$CURRENT_BRANCH" = "HEAD" ] || [ -z "$CURRENT_BRANCH" ]; then
    CURRENT_BRANCH="master"
fi

echo "==> Fetching latest changes on branch '${CURRENT_BRANCH}'..."
git fetch origin "${CURRENT_BRANCH}"
git reset --hard "origin/${CURRENT_BRANCH}"

echo "==> Building updated Docker containers..."
docker compose build --pull

echo "==> Starting containers..."
docker compose up -d --remove-orphans

echo "==> Cleaning up dangling images..."
docker image prune -f

echo "==> Verifying container status..."
docker compose ps
