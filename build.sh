#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

PRODUCTION=false
if [[ "${1:-}" == "--production" ]]; then
  PRODUCTION=true
fi

# Avoid requiring DB/Redis during deploy; log to stderr if storage/logs isn't writable yet.
artisan_clear() {
  CACHE_STORE=file LOG_CHANNEL=stderr php artisan "$@"
}

echo "==> Ensuring storage and cache directories exist and are writable..."
mkdir -p \
  storage/logs \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Clearing Laravel caches..."
artisan_clear config:clear --ansi
artisan_clear route:clear --ansi
artisan_clear view:clear --ansi
artisan_clear cache:clear --ansi
artisan_clear optimize:clear --ansi

echo "==> Rebuilding frontend assets..."
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
# shellcheck source=/dev/null
source "$NVM_DIR/nvm.sh"
nvm use
npm install
npm run build

if [[ "$PRODUCTION" == true ]]; then
  echo "==> Caching Laravel for production..."
  php artisan config:cache --ansi
  php artisan route:cache --ansi
  php artisan view:cache --ansi
fi

echo "==> Build complete."
