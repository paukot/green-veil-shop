# Green Veil Cosmetic Shop

Laravel 13 application running on PHP 8.5, FrankenPHP (serversideup), Vue 3 (Inertia v3), Tailwind CSS v4, MySQL 8.4, and Redis.

---

## Local Development

> **Note:** The included `compose.yaml` and `Dockerfile` are designed strictly for **production deployment** (immutable production image, baked assets, OPcache, FrankenPHP production runner) and are **not** intended for local development.

### Option 1: Lerd (Recommended)
This project includes `.lerd.yaml` providing PHP 8.5, MySQL, Redis, Mailpit, and Meilisearch, along with automated workers (Vite HMR, queue, scheduler):
```bash
# Start background services & workers
lerd start

# Install dependencies, set up .env, run migrations, and build assets
composer setup
```
App is accessible at `https://green-veil-cosmetic-shop.test`.

### Option 2: Local Host Tooling (PHP 8.5 & Node.js)
If developing directly on your host system:
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed

# Starts the PHP dev server, Vite HMR watcher, and queue worker concurrently
composer run dev
```

---

## Production Deployment

### 1. Server Requirements
- Linux instance (Ubuntu 22.04/24.04 recommended)
- Docker & Docker Compose v2 plugin
- Deploy user added to `docker` group (`sudo usermod -aG docker $USER`)

### 2. Initial Server Bootstrap
```bash
sudo mkdir -p /var/www/green-veil-cosmetic-shop
sudo chown -R $USER:$USER /var/www/green-veil-cosmetic-shop

git clone <REPO_URL> /var/www/green-veil-cosmetic-shop
cd /var/www/green-veil-cosmetic-shop

cp .env.docker.example .env
# Edit .env with your domain, APP_KEY, and secure DB credentials

chmod +x deploy.sh
./deploy.sh
```

Subsequent container updates and migrations run automatically via `./deploy.sh`.

---

## GitHub Actions CI/CD

On every push to `master` (and pull requests), GitHub Actions executes:
1. **CI**: `composer ci:check` (Pint, PHPStan, Vue/TypeScript, Pest) and `docker compose build`.
2. **CD**: Deploys via SSH to your KVM server running `./deploy.sh` on successful CI.

### Required Repository Secrets
Add these under **Settings** → **Secrets and variables** → **Actions**:

| Secret | Description | Example |
|---|---|---|
| `SSH_HOST` | Server IP or hostname | `203.0.113.10` |
| `SSH_USER` | Deploy user | `deploy` |
| `SSH_KEY` | Private SSH key (authorized on server) | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `DEPLOY_PATH` | Path on server | `/var/www/green-veil-cosmetic-shop` |
| `SSH_PORT` | *(Optional)* SSH port (default: `22`) | `22` |
| `SSH_PASSPHRASE` | *(Optional)* Passphrase if private key is encrypted | |
