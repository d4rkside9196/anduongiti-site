#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

for command_name in git php node npm mysql; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        printf 'Required command is unavailable: %s\n' "$command_name" >&2
        exit 127
    fi
done

if [[ "${DB_CONNECTION:-mysql}" != "mysql" ]]; then
    printf 'Release verification requires MySQL; DB_CONNECTION must be mysql.\n' >&2
    exit 1
fi

printf '\n==> git diff --check (excluding generated dependency metadata)\n'
git diff --check -- . ':(exclude)vendor/composer' ':(exclude)node_modules'

printf '\n==> npm ci --ignore-scripts (lockfile enforcement)\n'
npm ci --ignore-scripts

export BROWSER_DB_DATABASE="${BROWSER_DB_DATABASE:-anduongiti_browser_verify_$$}"
export PLAYWRIGHT_PORT="${PLAYWRIGHT_PORT:-8002}"
export CONTACT_THROTTLE_LIMIT="${CONTACT_THROTTLE_LIMIT:-1000}"
export LOGIN_THROTTLE_LIMIT="${LOGIN_THROTTLE_LIMIT:-1000}"

printf '\n==> php artisan test\n'
php artisan test

printf '\n==> npm run test:e2e (isolated browser DB %s on port %s)\n' "$BROWSER_DB_DATABASE" "$PLAYWRIGHT_PORT"
npm run test:e2e

printf '\n==> npm run test:a11y\n'
npm run test:a11y

printf '\n==> npm run build\n'
npm run build

printf '\n==> public/build binds to reviewed source (post-build diff)\n'
# The gate certifies the committed public/build that ships to production.
# Rebuilding must reproduce it byte-for-byte; drift (e.g. different
# build-time font content fetched from the CDN) fails the release instead
# of silently shipping unreviewed assets.
if ! git diff --exit-code -- public/build; then
    printf 'Rebuilt public/build differs from the committed tree — commit the\n' >&2
    printf 'regenerated output only after reviewing the diff (build inputs are\n' >&2
    printf 'release-affecting, including build-time font content).\n' >&2
    exit 1
fi
printf 'public/build post-build diff: PASS\n'
