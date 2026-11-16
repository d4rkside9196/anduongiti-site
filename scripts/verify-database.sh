#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

: "${LOCAL_ROOT_PASSWORD:?Set LOCAL_ROOT_PASSWORD for the local MySQL root account}"
: "${LOCAL_ADMIN_PASSWORD:?Set a disposable LOCAL_ADMIN_PASSWORD for generated schemas}"

MYSQL_BIN="${MYSQL_BIN:-mysql}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-mysqldump}"
DB_HOST="${MYSQL_HOST:-127.0.0.1}"
DB_PORT="${MYSQL_PORT:-3306}"
DB_USER="${MYSQL_ROOT_USER:-root}"
for required_command in php "$MYSQL_BIN" "$MYSQLDUMP_BIN"; do
    command -v "$required_command" >/dev/null 2>&1 || {
        printf 'Required command is unavailable: %s\n' "$required_command" >&2
        exit 127
    }
done

TOKEN="$$_$(date +%s)"
GENERATE_DB="anduongiti_s5_${TOKEN}_generate"
FULL_DB="anduongiti_s5_${TOKEN}_full"
SDA_DB="anduongiti_s5_${TOKEN}_sda"
UPGRADE_DB="anduongiti_s5_${TOKEN}_upgrade"
ROLLBACK_DB="anduongiti_s5_${TOKEN}_rollback"
# The upgrade/rollback certification must bind to the exact immutable
# previous-release artifact recorded for the promotion (a 40-hex commit
# SHA). Mutable ref names are refused so a moving origin/main can never
# silently change which baseline the rehearsal certifies.
: "${PREVIOUS_RELEASE_REF:?Set PREVIOUS_RELEASE_REF to the recorded previous-release commit SHA (40-hex), not a mutable ref}"
if [[ ! "$PREVIOUS_RELEASE_REF" =~ ^[0-9a-f]{40}$ ]]; then
    printf 'Refusing non-SHA PREVIOUS_RELEASE_REF: %s\n' "$PREVIOUS_RELEASE_REF" >&2
    exit 2
fi
PREVIOUS_RELEASE_SHA="$(git rev-parse "${PREVIOUS_RELEASE_REF}^{commit}")"
TEMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/anduongiti-s5-db.XXXXXXXX")"
export MYSQL_PWD="$LOCAL_ROOT_PASSWORD"

mysql_admin() {
    "$MYSQL_BIN" --no-defaults -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" "$@"
}

cleanup() {
    set +e
    mysql_admin -e "DROP DATABASE IF EXISTS \`$GENERATE_DB\`; DROP DATABASE IF EXISTS \`$FULL_DB\`; DROP DATABASE IF EXISTS \`$SDA_DB\`; DROP DATABASE IF EXISTS \`$UPGRADE_DB\`; DROP DATABASE IF EXISTS \`$ROLLBACK_DB\`;" >/dev/null 2>&1
    rm -rf "$TEMP_DIR"
}
trap cleanup EXIT

artisan_for() {
    local database="$1"
    shift
    DB_CONNECTION=mysql DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_DATABASE="$database" \
        DB_USERNAME="$DB_USER" DB_PASSWORD="$LOCAL_ROOT_PASSWORD" \
        ADMIN_EMAIL=admin@anduongiti.com ADMIN_PASSWORD="$LOCAL_ADMIN_PASSWORD" \
        php artisan "$@"
}

assert_business_data() {
    local database="$1"
    DB_CONNECTION=mysql DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_DATABASE="$database" \
        DB_USERNAME="$DB_USER" DB_PASSWORD="$LOCAL_ROOT_PASSWORD" \
        ADMIN_PASSWORD="$LOCAL_ADMIN_PASSWORD" php -r '
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $checks = [
                "categories" => App\Models\ProductCategory::count() === 8,
                "coffee_products" => App\Models\CoffeeProduct::count() === 16,
                "faqs" => App\Models\Faq::count() === 12,
                "legacy_products" => App\Models\Product::count() === 4,
                "robusta_bean_rows" => App\Models\CoffeeProduct::where("slug", "robusta-scratch-18-bean-coffee")->where("bean_variety", "robusta")->count() === 2,
                "arabica_bean_rows" => App\Models\CoffeeProduct::where("slug", "arabica-cau-dat-green-beans")->where("bean_variety", "arabica")->count() === 2,
                "migration_rows" => ($actualMigrations = Illuminate\Support\Facades\DB::table("migrations")->count()) === ($expectedMigrations = count(glob("database/migrations/*.php"))),
                "admin_password" => ($admin = App\Models\User::where("email", "admin@anduongiti.com")->first()) && Illuminate\Support\Facades\Hash::check((string) getenv("ADMIN_PASSWORD"), $admin->password),
                "lead_workflow_schema" => Illuminate\Support\Facades\Schema::hasColumns("leads", ["source_slug", "assigned_to", "coffee_product_id", "source_locale", "source_product_title", "source_category_slug", "first_response_at", "last_activity_at"]),
                "lead_notes_schema" => Illuminate\Support\Facades\Schema::hasColumns("lead_notes", ["lead_id", "user_id", "body"]),
            ];
            echo "migration ledger actual/expected: $actualMigrations/$expectedMigrations".PHP_EOL;
            foreach ($checks as $name => $passed) {
                echo $name.": ".($passed ? "PASS" : "FAIL").PHP_EOL;
            }
            if (in_array(false, $checks, true)) exit(1);
        '
}

table_counts() {
    local database="$1"
    local output="$2"
    local tables table
    tables="$(mysql_admin -N -e "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$database' ORDER BY TABLE_NAME")"
    : > "$output"
    while IFS= read -r table; do
        [[ -z "$table" ]] && continue
        mysql_admin -N -e "SELECT '$table', COUNT(*) FROM \`$database\`.\`$table\`" >> "$output"
    done <<< "$tables"
}

printf '==> Create fresh disposable schema %s\n' "$GENERATE_DB"
mysql_admin -e "CREATE DATABASE \`$GENERATE_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE \`$FULL_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE \`$SDA_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE \`$UPGRADE_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE \`$ROLLBACK_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
artisan_for "$GENERATE_DB" migrate:fresh --seed --force

printf '==> Export temporary full/schema/data artifacts\n'
"$MYSQLDUMP_BIN" -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" --single-transaction --skip-comments --no-tablespaces --add-drop-table --default-character-set=utf8mb4 "$GENERATE_DB" > "$TEMP_DIR/full.sql"
"$MYSQLDUMP_BIN" -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" --single-transaction --skip-comments --no-tablespaces --add-drop-table --default-character-set=utf8mb4 --no-data "$GENERATE_DB" > "$TEMP_DIR/schema.sql"
"$MYSQLDUMP_BIN" -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" --single-transaction --skip-comments --no-tablespaces --add-drop-table --default-character-set=utf8mb4 --no-create-info "$GENERATE_DB" > "$TEMP_DIR/data.sql"

printf '==> Import full and schema+data into separate schemas\n'
mysql_admin --default-character-set=utf8mb4 "$FULL_DB" < "$TEMP_DIR/full.sql"
mysql_admin --default-character-set=utf8mb4 "$SDA_DB" < "$TEMP_DIR/schema.sql"
mysql_admin --default-character-set=utf8mb4 "$SDA_DB" < "$TEMP_DIR/data.sql"

for database in "$GENERATE_DB" "$FULL_DB" "$SDA_DB"; do
    printf '==> Verify %s migration ledger and business data\n' "$database"
    status="$(artisan_for "$database" migrate:status --no-ansi)"
    printf '%s\n' "$status"
    if grep -q 'Pending' <<< "$status"; then
        printf 'Pending migrations in %s\n' "$database" >&2
        exit 1
    fi
    assert_business_data "$database"
done

table_counts "$GENERATE_DB" "$TEMP_DIR/generate.counts"
for database in "$FULL_DB" "$SDA_DB"; do
    table_counts "$database" "$TEMP_DIR/$database.counts"
    diff -u "$TEMP_DIR/generate.counts" "$TEMP_DIR/$database.counts"
done
printf 'Exact table row counts: PASS\n'

printf '==> Load real previous release artifact %s (%s)\n' "$PREVIOUS_RELEASE_REF" "$PREVIOUS_RELEASE_SHA"
git show "$PREVIOUS_RELEASE_REF:database/anduongiti.sql" > "$TEMP_DIR/previous-release.sql"
sha256_of() {
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | awk '{print $1}'
    else
        shasum -a 256 "$1" | awk '{print $1}'
    fi
}
PREVIOUS_DUMP_SHA="$(sha256_of "$TEMP_DIR/previous-release.sql")"
printf 'Previous-release dump sha256: %s\n' "$PREVIOUS_DUMP_SHA"
mysql_admin --default-character-set=utf8mb4 "$UPGRADE_DB" < "$TEMP_DIR/previous-release.sql"
previous_status="$(artisan_for "$UPGRADE_DB" migrate:status --no-ansi)"
printf '%s\n' "$previous_status"
grep -q '2026_11_13_000007_add_workflow_fields_to_leads_table.*Pending' <<< "$previous_status"
grep -q '2026_11_13_000008_create_lead_notes_table.*Pending' <<< "$previous_status"
if mysql_admin -N -e "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$UPGRADE_DB' AND TABLE_NAME = 'lead_notes'" | grep -q '^1$'; then
    printf 'Previous artifact unexpectedly contains lead_notes\n' >&2
    exit 1
fi

# Give only the disposable previous-release admin a known rehearsal credential.
DB_CONNECTION=mysql DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_DATABASE="$UPGRADE_DB" \
    DB_USERNAME="$DB_USER" DB_PASSWORD="$LOCAL_ROOT_PASSWORD" ADMIN_PASSWORD="$LOCAL_ADMIN_PASSWORD" php -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $admin = App\Models\User::where("email", "admin@anduongiti.com")->firstOrFail();
        $admin->password = getenv("ADMIN_PASSWORD");
        $admin->save();
    '
table_counts "$UPGRADE_DB" "$TEMP_DIR/previous.counts"
"$MYSQLDUMP_BIN" -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" --single-transaction --skip-comments --no-tablespaces --add-drop-table --default-character-set=utf8mb4 "$UPGRADE_DB" > "$TEMP_DIR/previous-backup.sql"

printf '==> Upgrade the real previous schema and verify business data\n'
artisan_for "$UPGRADE_DB" migrate --force
upgrade_status="$(artisan_for "$UPGRADE_DB" migrate:status --no-ansi)"
printf '%s\n' "$upgrade_status"
! grep -q 'Pending' <<< "$upgrade_status"
assert_business_data "$UPGRADE_DB"
table_counts "$UPGRADE_DB" "$TEMP_DIR/upgrade.counts"
grep -Ev $'^(activity_logs|cache|sessions)\t' "$TEMP_DIR/generate.counts" > "$TEMP_DIR/generate.business.counts"
grep -Ev $'^(activity_logs|cache|sessions)\t' "$TEMP_DIR/upgrade.counts" > "$TEMP_DIR/upgrade.business.counts"
diff -u "$TEMP_DIR/generate.business.counts" "$TEMP_DIR/upgrade.business.counts"

printf '==> Restore the previous backup into separate schema %s\n' "$ROLLBACK_DB"
mysql_admin --default-character-set=utf8mb4 "$ROLLBACK_DB" < "$TEMP_DIR/previous-backup.sql"
rollback_status="$(artisan_for "$ROLLBACK_DB" migrate:status --no-ansi)"
printf '%s\n' "$rollback_status"
grep -q '2026_11_13_000007_add_workflow_fields_to_leads_table.*Pending' <<< "$rollback_status"
grep -q '2026_11_13_000008_create_lead_notes_table.*Pending' <<< "$rollback_status"
table_counts "$ROLLBACK_DB" "$TEMP_DIR/rollback.counts"
grep -Ev $'^(activity_logs|cache|sessions)\t' "$TEMP_DIR/previous.counts" > "$TEMP_DIR/previous.business.counts"
grep -Ev $'^(activity_logs|cache|sessions)\t' "$TEMP_DIR/rollback.counts" > "$TEMP_DIR/rollback.business.counts"
diff -u "$TEMP_DIR/previous.business.counts" "$TEMP_DIR/rollback.business.counts"
printf 'Real previous-artifact upgrade and separate-schema rollback: PASS (previous-release %s, dump sha256=%s)\n' "$PREVIOUS_RELEASE_SHA" "$PREVIOUS_DUMP_SHA"

printf '==> Run committed MySQL test suite\n'
php artisan test

printf 'Disposable MySQL verification: PASS\n'
