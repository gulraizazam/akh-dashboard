#!/usr/bin/env bash
#
# Deploy the dashboard from git.
#
# Usage (on the server):
#   cd ~/domains/nexthealth.com.pk/public_html/dashboard && ./deploy.sh
#
# Everything this script touches lives inside this directory. It never writes
# outside the application root.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

# Shared hosting disables proc_open on some PHP builds; Composer and a few
# artisan commands need it, so prefer a build that has it.
pick_php() {
    for p in /opt/alt/php82/usr/bin/php /opt/alt/php84/usr/bin/php /usr/bin/php php; do
        if command -v "$p" >/dev/null 2>&1 \
           && "$p" -r 'exit(function_exists("proc_open") ? 0 : 1);' 2>/dev/null; then
            echo "$p"; return
        fi
    done
    echo php
}
PHP="$(pick_php)"
echo "==> PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

echo "==> Pulling latest from origin/main"
git fetch --depth=1 origin main
git checkout -f -B main origin/main
echo "    HEAD: $(git rev-parse --short HEAD)"

echo "==> Installing dependencies"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
"$PHP" artisan package:discover --ansi

echo "==> Running migrations"
"$PHP" artisan migrate --force

echo "==> Rebuilding caches"
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo "==> Fixing permissions"
chmod -R 775 storage bootstrap/cache
chmod 600 .env
[ -f database/database.sqlite ] && chmod 664 database/database.sqlite

echo "==> Done. https://dashboard.nexthealth.com.pk"
