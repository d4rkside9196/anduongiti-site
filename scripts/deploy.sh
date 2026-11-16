#!/usr/bin/env bash
# Run this on the Hostinger server (via SSH) after uploading new code.
#
# Usage:
#   cd ~/domains/<your-domain>/public_html
#   bash scripts/deploy.sh

set -euo pipefail

sha256_of() {
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | awk '{print $1}'
    else
        shasum -a 256 "$1" | awk '{print $1}'
    fi
}

echo "[1/5] Backing up MySQL DB..."
# Backups live OUTSIDE the served web root (matching the release runbook);
# writing them into public_html left full database dumps one guessed URL
# away from the internet.
BACKUP_DIR="../release-backups"
mkdir -p "$BACKUP_DIR"
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"' || true)
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"')

if ! mysql --no-defaults -h"$DB_HOST" -u"$DB_USERNAME" -e "USE \`$DB_DATABASE\`" >/dev/null 2>&1; then
    echo "  (database $DB_DATABASE does not exist yet — first deploy; skipping backup)"
else
    # Credentials travel via MYSQL_PWD (env), never on the command line:
    # an argv password is readable from /proc/<pid>/cmdline by any local
    # process on a shared host.
    export MYSQL_PWD="$DB_PASSWORD"
    STAMP="$(date +%Y%m%d-%H%M%S)"
    BACKUP_FILE="$BACKUP_DIR/$STAMP.sql"
    # Flags match docs/superpowers/runbooks/production-release.md:
    # --single-transaction for a consistent dump, --no-tablespaces so the
    # shared MySQL user does not need the PROCESS privilege.
    if ! mysqldump --single-transaction --no-tablespaces \
        --skip-comments --add-drop-table --default-character-set=utf8mb4 \
        -h"$DB_HOST" -u"$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE"; then
        echo "  mysqldump FAILED — aborting before migrations; fix the backup first." >&2
        exit 1
    fi
    unset MYSQL_PWD
    [[ -s "$BACKUP_FILE" ]] || { echo "  backup is empty — aborting." >&2; exit 1; }
    echo "  backup: $BACKUP_FILE (sha256=$(sha256_of "$BACKUP_FILE"))"
fi

echo "[2/5] Installing composer deps..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "[3/5] Running DB migrations..."
php artisan migrate --force

echo "[4/5] Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "[5/5] Caching config and views..."
# IMPORTANT: do NOT run `php artisan route:cache`. The
# mcamara/laravel-localization package builds the locale prefix at
# request time (the `LaravelLocalization::setLocale()` call inside the
# Route::group prefix), so caching routes serialises an empty prefix
# and every /vi/* URL returns 404. Config and view caches are fine.
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

echo
echo "✓ Deploy complete. Site should be live."
echo "  Logs: tail -50 storage/logs/laravel.log"
