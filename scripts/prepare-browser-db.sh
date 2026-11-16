#!/usr/bin/env bash
set -euo pipefail

# Recreate only the disposable MySQL browser database. Never touch the
# development database or any committed release dump.
DB_NAME="${BROWSER_DB_DATABASE:-anduongiti_browser}"
DB_USER="${BROWSER_DB_ROOT_USER:-root}"
DB_PASSWORD="${BROWSER_DB_ROOT_PASSWORD:-123456}"
MYSQL_BIN="${MYSQL_BIN:-/Applications/ServBay/package/mysql/current/bin/mysql}"
LOCK_DIR="${TMPDIR:-/tmp}/anduongiti-browser-db-${DB_NAME}.lock"

# Concurrent Playwright gates share this disposable database; serialize setup.
until mkdir "$LOCK_DIR" 2>/dev/null; do
  sleep 0.1
done
trap 'rmdir "$LOCK_DIR"' EXIT

"$MYSQL_BIN" -h127.0.0.1 -u"$DB_USER" -p"$DB_PASSWORD" \
  -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

DB_CONNECTION=mysql \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE="$DB_NAME" \
DB_USERNAME="$DB_USER" \
DB_PASSWORD="$DB_PASSWORD" \
php artisan migrate:fresh --seed --force
