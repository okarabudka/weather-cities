# symfony-skeleton-bootstrap

Infrastructure-only repository that bootstraps a fresh Symfony project on first container start.
No Symfony source files are stored in git — the app lives in a Docker named volume.

## Quick start

```bash
make up-containers
```

That's it. On first start the `app` container automatically:

1. Runs `composer create-project symfony/skeleton` (latest stable by default)
2. Installs `symfony/orm-pack`, `symfony/maker-bundle`, `symfony/test-pack`, `phpstan/phpstan`
3. Configures SQLite as the default database in `.env.local`
4. Creates the database and clears the cache

Wait **1–3 minutes** (composer download), then open http://localhost:8080
Check progress: `make logs`

## Choose Symfony version

By default the latest stable Symfony is installed. To pin a specific version set it **before first run**:

```bash
SYMFONY_VERSION=7.2.* make up-containers
```

Or set it in `.env.local` before first run:

```
SYMFONY_VERSION=7.2.*
```

> **Note:** changing `SYMFONY_VERSION` has no effect after the volume is initialized.
> To start fresh with a different version run `make reset`.

## Optional services (via profiles)

```bash
docker compose --profile mysql up -d   # MySQL 8.4
docker compose --profile mail  up -d   # Mailpit (SMTP testing)
docker compose --profile cache up -d   # Redis
docker compose --profile node  up -d   # Node.js 22
```

## URLs

- http://localhost:8080 → Symfony
- http://localhost:8025 → Mailpit web UI (only with `mail` profile)

## Available commands

```bash
make help
```

Key commands:

| Command | Description |
|---------|-------------|
| `make up-containers` | Start containers, bootstrap on first run |
| `make reset` | Destroy volume and re-bootstrap from scratch |
| `make bash` | Shell inside app container |
| `make migrate` | Run Doctrine migrations |
| `make test` | PHPUnit |
| `make phpstan` | Static analysis |
| `make lint` | PHP syntax check |
| `make export-app` | Copy generated app to `./app/` |
| `make finalize` | **One-way**: replace repo with generated app (asks confirmation) |

## Workflow: starting a new project

1. Clone this repo into your new project folder
2. `make up-containers` — wait for bootstrap
3. Work on the Symfony app via `make bash`
4. When ready to commit the app to git, run `make finalize`
   - Copies the generated app from Docker volume to repo root
   - Removes all bootstrap infrastructure files
   - What remains is a plain Symfony project ready to commit

## Switching from SQLite to MySQL

By default the app uses SQLite. To switch to MySQL:

```bash
# 1. Start MySQL
docker compose --profile mysql up -d

# 2. Update DATABASE_URL inside the container
make bash
# Inside container:
echo 'DATABASE_URL="mysql://symfony:secret@mysql:3306/app?serverVersion=8.4"' >> .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
exit
```

## PHP settings

Dev overrides: `docker/php/conf.d/dev/` (mounted into container).

## Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `SYMFONY_VERSION` | *(latest)* | Symfony version constraint, e.g. `7.2.*` |
| `APP_PORT` | `8080` | HTTP port |
| `MYSQL_DATABASE` | `app` | MySQL DB name (profile: mysql) |
| `MYSQL_USER` | `symfony` | MySQL user (profile: mysql) |
| `MYSQL_PASSWORD` | `secret` | MySQL password (profile: mysql) |
| `MYSQL_ROOT_PASSWORD` | `root` | MySQL root password (profile: mysql) |
| `MYSQL_PORT` | `3306` | MySQL host port (profile: mysql) |
| `REDIS_PORT` | `6379` | Redis host port (profile: cache) |
| `MAILPIT_HTTP_PORT` | `8025` | Mailpit web UI port (profile: mail) |
| `MAILPIT_SMTP_PORT` | `1025` | Mailpit SMTP port (profile: mail) |
