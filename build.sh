#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

PRODUCTION=false
if [[ "${1:-}" == "--production" ]]; then
  PRODUCTION=true
fi

echo "==> Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Clearing Laravel caches..."
php artisan config:clear --ansi
php artisan route:clear --ansi
php artisan view:clear --ansi
php artisan cache:clear --ansi
php artisan optimize:clear --ansi

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
