# Deploying green-veil-cosmetic-shop

## Day-to-day

| Task                                  | Command                                                                                           |
| :------------------------------------ | :------------------------------------------------------------------------------------------------ |
| Deploy the latest `master`            | push to `master` (CI) or `DEPLOY_BRANCH=master ./deploy.sh`                                       |
| Deploy another branch                 | `DEPLOY_BRANCH=my-branch ./deploy.sh`                                                             |
| See what runs                         | `./deploy.sh status`                                                                              |
| Follow logs                           | `./deploy.sh logs`                                                                                |
| Back up now (MySQL + uploaded images) | `./deploy.sh backup`                                                                              |
| Copy backups to your machine          | `./deploy.sh pull-backups`                                                                        |
| Restore the database                  | `./deploy.sh restore backups/db-<STAMP>-<label>.sql.gz`                                           |
| Restore uploaded images               | `./deploy.sh restore backups/files-<STAMP>-<label>.tar.gz`                                        |
| Run an artisan command                | `ssh ... 'cd /var/www/green-veil-cosmetic-shop && docker compose exec app php artisan <command>'` |

A restore takes a safety backup first, then stops the shop, loads the dump into MySQL (the dump drops and recreates
the tables) and starts everything again. If loading fails the stack is started again and the safety backup is in `backups/`.

A nightly backup in addition to the per-deploy one (server crontab of the deploy user):

```
0 3 * * * cd /var/www/green-veil-cosmetic-shop && ./backup.sh >> backups/cron.log 2>&1
```
