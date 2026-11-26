# Deploying green-veil-cosmetic-shop

One command (`./deploy.sh`) or a push to `master` deploys the site. The server pulls the commit with git and
**builds the image itself**; nothing is uploaded from your machine. This file is both the **migration plan**
(section 1) for the server that currently runs the old setup, and the **day-to-day guide** (sections 2-4).

The stack: the FrankenPHP app container (plus a queue worker and the scheduler, MySQL and Redis) behind a small **Caddy proxy** that terminates HTTPS with a
Cloudflare Origin Certificate on one custom port. Cloudflare reaches that port over **IPv4** (an Origin Rule
rewrites the port), so the IPv6 setup and the host-level reverse proxy are no longer needed.

```
Visitor --https--> Cloudflare (Full strict) --https :PUBLIC_PORT--> Caddy proxy --http--> app (FrankenPHP)
                   Origin Rule: port 443 -> PUBLIC_PORT                                     |
                                                                          MySQL, Redis (volumes) + uploads in the `storage` volume
```

Everything below assumes the same server as queue-guard and resume-landing-page (`lee154.mikrus.xyz`, SSH port `10154`, shared IPv4
`65.109.114.245`). Every app on that server needs **its own** public port. If this site runs on another
server, the port can stay `8443`.

## What changed in the repo

| Before | Now |
| :--- | :--- |
| `deploy.sh` ran on the server by hand | `./deploy.sh` runs on your workstation or in CI and drives the server over SSH (same git pull + build as before) |
| App published `APP_PORT` (8080) for a host Nginx/Caddy, IPv6 through Cloudflare; MySQL/Redis on host ports 3307/6380 | Caddy proxy publishes **one** port (`PUBLIC_PORT`); app, MySQL and Redis are not published |
| CI used a third-party SSH action with a mutable tag | CI runs `./deploy.sh` with a pinned host key (no `StrictHostKeyChecking=no`) |
| No queue worker, no scheduler | `worker` (`queue:work`) and `scheduler` (`schedule:work`) services: Stripe webhooks and image variants were queued but **never processed** before, and `orders:sweep-stale` never ran |
| No backups | A backup (MySQL dump **and** uploaded images) is taken automatically before every deploy |
| Volumes named after the checkout directory | Compose project name is pinned (`name: green-veil-cosmetic-shop`), so moving the folder cannot orphan the data |
| `MYSQL_PASSWORD` defaulted to `secret` independently of `DB_PASSWORD` | It follows `DB_PASSWORD` unless set (only matters for a brand-new database volume) |

What a deploy does, in order: `git fetch` + checkout of the branch (local changes on the server are discarded) →
lock → pre-deploy backup → `docker compose build --pull` → `up -d --wait` (the app container runs migrations and `storage:link`
and reports healthy; worker and scheduler start after it) → HTTPS check against the proxy. If the stack
does not become healthy the deploy fails loudly and the data is untouched.

Because the server pulls from GitHub, **commit and push before you deploy**. Untracked server files (`.env`,
`certs/`, `backups/`) are never touched by the update.

## 1. Migration plan for the existing server

The old checkout at `/var/www/green-veil-cosmetic-shop` is reused as is, with the same `.env` (APP_KEY, passwords, Stripe
keys), the same Docker volumes and the same project name. Do not change `DB_PASSWORD` / `DB_ROOT_PASSWORD` afterwards:
MySQL only reads them when the data volume is first created. The old `APP_PORT`, `SSL_MODE` and `FORWARD_*` lines in
`.env` are unused now and can stay. Plan for a few minutes of downtime between step 5 and step 7.

### 1. Take a backup with the OLD setup (on the server)

The new deploy backs up by itself, but do this once by hand and keep a copy off the server:

```bash
cd /var/www/green-veil-cosmetic-shop
mkdir -p backups
docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --no-tablespaces "$MYSQL_DATABASE"' | gzip > backups/db-before-migration.sql.gz
docker compose exec -T app tar -czf - -C /var/www/html/storage/app public > backups/images-before-migration.tar.gz
gzip -t backups/*.gz && ls -lh backups
docker volume ls | grep green-veil       # expect: ..._mysql_data, ..._redis_data, ..._storage
```

Copy `backups/` to your workstation (`scp -P 10154 -r user@lee154.mikrus.xyz:/var/www/green-veil-cosmetic-shop/backups .`).
If `docker volume ls` shows a different prefix than `green-veil-cosmetic-shop_`, **stop** and change `name:` at the top
of `compose.yaml` to that prefix before continuing.

### 2. Pick a port and a certificate

- Port: choose a port the server forwards that no other app uses, e.g. `40000` (queue-guard and resume-landing-page need their own).
- Certificate: Cloudflare dashboard -> your domain -> SSL/TLS -> Origin Server -> Create Certificate. A certificate
  for `*.yourdomain` and `yourdomain` can be reused by all three apps. Save `origin.pem` and `origin.key`
  locally (never commit them).
- Keep SSL/TLS mode on **Full (strict)**.

### 3. Let the deploy user own the checkout (on the server)

```bash
sudo chown -R deploy:deploy /var/www/green-veil-cosmetic-shop
sudo -u deploy git -C /var/www/green-veil-cosmetic-shop fetch origin    # must work without a password prompt
```

The deploy user must be in the `docker` group. If the repository is private, the server needs read access
(a deploy key); it already does if the old `deploy.sh` worked there.

### 4. Configure and test from your workstation

```bash
cp .deploy.env.example .deploy.env
# edit: DEPLOY_HOST=lee154.mikrus.xyz  DEPLOY_SSH_PORT=10154  DEPLOY_USER=deploy
#       DEPLOY_PATH=/var/www/green-veil-cosmetic-shop  DEPLOY_PUBLIC_PORT=40000
#       DEPLOY_APP_URL=https://<your hostname>
ssh-keygen -F '[lee154.mikrus.xyz]:10154' || ssh-keyscan -p 10154 lee154.mikrus.xyz >> ~/.ssh/known_hosts
./deploy.sh --dry-run                    # prints the exact ssh commands, changes nothing
./deploy.sh cert origin.pem origin.key   # uploads the certificate once (over ssh, no extra tools)
```

### 5. Push the changes and deploy them

Push these changes to a **branch** first, so `master` (and CI) stay untouched until you verified the result:

```bash
git switch -c deploy-update && git add -A && git commit -m "feat: caddy proxy, worker, scheduler and one-command deploy" && git push -u origin deploy-update
DEPLOY_BRANCH=deploy-update ./deploy.sh
```

The server checks out that branch, backs up the running site, builds the image, and recreates the app container
(the old published ports 80/443 disappear) next to the new proxy. You should end with
`HTTPS check passed on port 40000`. The existing data is picked up from the `mysql_data` and `storage` volumes;
migrations only add what is missing.

### 6. Check it before touching Cloudflare

```bash
./deploy.sh status                       # app, mysql, redis, proxy: healthy; worker and scheduler: running
curl -ksI https://65.109.114.245:40000/up    # HTTP/2 200 (-k: the certificate is not for an IP address)
```

If `/up` answers but the shop shows no data, run `docker volume ls` on the server and compare with step 1;
the pre-deploy backup is in `backups/` on the server.

The queue worker now starts for the first time. Jobs that piled up in Redis while nothing consumed them
(Stripe webhooks, image variants) are processed right away; webhook handling is idempotent. Backfill the
image variants that were never generated, and check that nothing is stuck:

```bash
ssh -p 10154 deploy@lee154.mikrus.xyz 'cd /var/www/green-veil-cosmetic-shop && docker compose exec app php artisan app:images:process && docker compose logs --tail=50 worker'
```

### 7. Switch Cloudflare

1. DNS: the site's record must be an **A** record -> `65.109.114.245`, proxied (orange cloud). **Delete the AAAA record** (the old IPv6 route).
2. Rules -> Origin Rules -> Create: *Hostname equals* `<your hostname>` -> *Destination Port: Rewrite to* `40000`.
3. SSL/TLS -> Overview: **Full (strict)**.
4. Open the shop. Check the admin login, product images, and place a test order (Stripe test keys, or the offline gateway) to confirm the checkout and webhook path.

### 8. Merge and set up GitHub Actions

Merge `deploy-update` into `master`. Then Repository -> Settings -> Environments -> `production`:

| Kind | Name | Value |
| :--- | :--- | :--- |
| secret | `SSH_HOST` | `lee154.mikrus.xyz` |
| secret | `SSH_USER` | `deploy` |
| secret | `SSH_PORT` | `10154` |
| secret | `SSH_KEY` | private key of a **passphrase-less** deploy key (public half in the deploy user's `authorized_keys`) |
| secret | `SSH_KNOWN_HOSTS` | output of `ssh-keygen -F '[lee154.mikrus.xyz]:10154'` (the key line, not the comment) |
| variable | `DEPLOY_PATH` | `/var/www/green-veil-cosmetic-shop` |
| variable | `DEPLOY_APP_URL` | `https://<your hostname>` |
| variable | `DEPLOY_PUBLIC_PORT` | `40000` |

`SSH_PASSPHRASE` is no longer used. The first run after the merge also switches the server checkout back to
`master`. Until `SSH_KNOWN_HOSTS` exists the CI deploy job fails safely at the host-key check, so add it before merging.

### 9. Clean up

Remove the old AAAA record and any host Nginx/Caddy site for this app. Leave the Docker volumes alone. Your Stripe
webhook endpoint URL does not change (same hostname).

## 2. Day-to-day

| Task | Command |
| :--- | :--- |
| Deploy the latest `master` | push to `master` (CI) or `DEPLOY_BRANCH=master ./deploy.sh` |
| Deploy another branch | `DEPLOY_BRANCH=my-branch ./deploy.sh` |
| See what runs | `./deploy.sh status` |
| Follow logs | `./deploy.sh logs` |
| Back up now (MySQL + uploaded images) | `./deploy.sh backup` |
| Copy backups to your machine | `./deploy.sh pull-backups` |
| Restore the database | `./deploy.sh restore backups/db-<STAMP>-<label>.sql.gz` |
| Restore uploaded images | `./deploy.sh restore backups/files-<STAMP>-<label>.tar.gz` |
| Run an artisan command | `ssh ... 'cd /var/www/green-veil-cosmetic-shop && docker compose exec app php artisan <command>'` |

A restore takes a safety backup first, then stops the shop, loads the dump into MySQL (the dump drops and recreates
the tables) and starts everything again. If loading fails the stack is started again and the safety backup is in `backups/`.

A nightly backup in addition to the per-deploy one (server crontab of the deploy user):

```
0 3 * * * cd /var/www/green-veil-cosmetic-shop && ./backup.sh >> backups/cron.log 2>&1
```

## 3. How the data stays safe

- MySQL lives in the `mysql_data` volume, Redis (AOF) in `redis_data`, uploaded images in `storage`
  (`storage/app/public`). Deploys replace containers and images only.
- **Never** run `docker compose down -v`, `docker volume rm` or `docker system prune --volumes` on the server.
- Backups are SQL dumps (`.sql.gz`) taken with a consistent snapshot while the shop is live, plus a tar of the
  uploaded images. Product image variants (`*-400.webp`, ...) are included; they can also be regenerated with
  `php artisan app:images:process`.
- The checkout update never touches ignored or untracked files such as `.env`, `certs/` and `backups/`.

## 4. Troubleshooting

| Symptom | Likely cause |
| :--- | :--- |
| `cannot reach ... on port` | wrong `DEPLOY_SSH_PORT`, key not authorised, or the host key is not in known_hosts |
| `... is not a git checkout` | `DEPLOY_PATH` is wrong, or clone once (or set `DEPLOY_REPO_URL`) |
| `git fetch` fails on the server | the server cannot reach GitHub or lacks read access to a private repository |
| `certs/origin.pem ... are missing` | run `./deploy.sh cert origin.pem origin.key` once |
| `the stack did not become healthy` | read the container logs printed above the error; the data is untouched |
| Cloudflare 521 / 522 | Origin Rule port differs from `DEPLOY_PUBLIC_PORT`, or the port is not forwarded to the server |
| Cloudflare 525 / 526 | certificate does not cover the hostname, or the mode is not Full (strict) |
| Site works on `https://host:PORT` but not on `https://host` | the Origin Rule is missing or its hostname does not match |
| Database looks empty after a deploy | the volume prefix changed; compare `docker volume ls` with `green-veil-cosmetic-shop_mysql_data` and restore from `backups/` |
| Orders stay pending / images have no variants | `docker compose logs worker`; the worker must be running (`./deploy.sh status`) |
| `Access denied for user` after changing `.env` | MySQL reads its passwords only on first start; change them with `ALTER USER`, not by editing `.env` alone |
