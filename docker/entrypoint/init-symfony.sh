#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www"

if [ ! -f "$APP_DIR/.initialized" ]; then
  echo "==> Initializing Symfony skeleton in $APP_DIR"
  mkdir -p "$APP_DIR"
  cd "$APP_DIR"

  # Empty SYMFONY_VERSION means latest stable
  if [ -n "${SYMFONY_VERSION:-}" ]; then
    PACKAGE="symfony/skeleton:${SYMFONY_VERSION}"
  else
    PACKAGE="symfony/skeleton"
  fi

  # Clean any leftover files from a previous failed init
  find "$APP_DIR" -mindepth 1 -delete

  composer create-project "$PACKAGE" . --no-interaction --no-progress

  composer require symfony/orm-pack --no-interaction --no-progress
  composer require --dev symfony/maker-bundle symfony/test-pack phpstan/phpstan --no-interaction --no-progress

  cat > .env.local <<'ENVLOCAL'
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
ENVLOCAL

  # Remove Symfony's auto-generated compose files (we provide our own)
  rm -f compose.yaml compose.override.yaml

  # Copy standalone Docker setup into the app
  cp -r /docker-app/. .

  php bin/console cache:clear

  chown -R www-data:www-data var

  touch /var/www/.initialized
  echo "==> Symfony initialized successfully"
fi

exec "$@"
