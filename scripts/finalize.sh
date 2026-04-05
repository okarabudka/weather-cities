#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
EXPORT_DIR="$ROOT_DIR/_export"

echo "WARNING: This is a one-way operation."
echo "All bootstrap infrastructure files will be deleted and replaced with the generated Symfony app."
echo ""
read -r -p "Are you sure? (yes/N): " confirm
if [ "$confirm" != "yes" ]; then
  echo "Aborted."
  exit 0
fi

if [ -d "$EXPORT_DIR" ]; then
  echo "_export directory already exists. Remove it and retry."
  exit 1
fi

mkdir -p "$EXPORT_DIR"

# Export generated Symfony app from volume into _export, owned by current host user
( cd "$ROOT_DIR" && docker compose run --rm --entrypoint sh \
    -v "$EXPORT_DIR:/export" app \
    -c "cp -a /var/www/. /export && rm -f /export/.initialized && chown -R $(id -u):$(id -g) /export" )

# Guard: ensure it's actually a Symfony app
if [ ! -f "$EXPORT_DIR/composer.json" ]; then
  rm -rf "$EXPORT_DIR"
  echo "ERROR: app volume appears empty or not initialized. Run 'make up-containers' first and wait for bootstrap to complete."
  exit 1
fi

# Remove bootstrap-only files
rm -rf \
  "$ROOT_DIR/docker" \
  "$ROOT_DIR/docker-app" \
  "$ROOT_DIR/docker-compose.yml" \
  "$ROOT_DIR/Dockerfile" \
  "$ROOT_DIR/Makefile" \
  "$ROOT_DIR/.dockerignore" \
  "$ROOT_DIR/README.md" \
  "$ROOT_DIR/.gitignore" \
  "$ROOT_DIR/.github" \
  "$ROOT_DIR/scripts"

# Copy exported app to repo root
cp -a "$EXPORT_DIR/." "$ROOT_DIR/"

# Cleanup
rm -rf "$EXPORT_DIR"

echo "Done. Repository now contains only the Symfony app."
echo "Next: review the files, then git add . && git commit"
