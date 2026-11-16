# Database artifacts

Repo là **MySQL-only** và duy trì các artifact export từ disposable MySQL schemas:

- `anduongiti.sql`: full schema + data + migration ledger cho fresh install.
- `anduongiti-schema.sql`: schema-only để phân tích hoặc dùng cùng `anduongiti-data.sql`.
- `anduongiti-data.sql`: data + migration ledger cho workflow schema+data.

Local development, PHPUnit và browser tests dùng các MySQL databases riêng (`anduongiti_test`, `anduongiti_browser`). Không có SQLite snapshot trong project.

## Chọn file import MySQL

| File | Mục đích | Nội dung |
|---|---|---|
| **`anduongiti.sql`** | 🥇 **Recommended cho fresh install** | Toàn bộ schema + data + bảng và records `migrations` |
| `anduongiti-schema.sql` | Phân tích schema; chỉ dùng trong deploy khi import kèm `anduongiti-data.sql` | DROP + CREATE TABLE, không có data |
| `anduongiti-data.sql` | Phần data của workflow schema+data | INSERT cho toàn bộ tables, gồm migration ledger |

**Không import schema-only rồi chạy `db:seed` để deploy.** Cách đó để bảng `migrations` rỗng; lần `migrate` tiếp theo sẽ cố tạo lại các bảng đã tồn tại. Schema-only chỉ dùng để phân tích. Fresh install phải dùng full `anduongiti.sql`, hoặc cặp schema+data có migration ledger.

## Fresh install lên Hostinger

### Cách 1 — Full dump (recommended)

phpMyAdmin: tạo database trống → **Import** → chọn `anduongiti.sql` → **Go**.

Hoặc qua SSH:

```bash
mysql -u <DB_USERNAME> -p <DB_DATABASE> < database/anduongiti.sql
```

### Cách 2 — Schema + data (có ledger)

Chỉ dùng với database trống và import đúng thứ tự:

```bash
mysql -u <DB_USERNAME> -p <DB_DATABASE> < database/anduongiti-schema.sql
mysql -u <DB_USERNAME> -p <DB_DATABASE> < database/anduongiti-data.sql
php artisan migrate:status
```

`migrate:status` phải báo tất cả migration là `Ran`, không có `Pending`. Không chạy `db:seed` như một thay thế cho data dump.

### Update database đã được quản lý bằng migrations

Không import lại full dump lên production vì dump có `DROP TABLE`. Deploy code mới rồi chạy:

```bash
php artisan migrate --force
php artisan migrate:status
```

## Admin credentials trong snapshot/dump

Email: `admin@anduongiti.com`. Không có password mặc định dùng chung. Khi tạo database mới, seeder dùng `ADMIN_PASSWORD` từ environment hoặc tạo random credential không dùng để đăng nhập; chạy lại seeder không thay password hiện có.

⚠️ Đặt `LOCAL_ADMIN_PASSWORD` là secret riêng khi regenerate, không commit giá trị thật. Đổi password trước khi go-live từ `/admin` → account menu.

## Charset

Các MySQL dump dùng `utf8mb4 / utf8mb4_unicode_ci`, phù hợp tiếng Việt và cấu hình mặc định của Hostinger.

## MySQL test và browser databases

PHPUnit dùng `anduongiti_test`; browser tests dùng `anduongiti_browser` disposable. Browser setup recreates chỉ database này bằng `scripts/prepare-browser-db.sh` và không bao giờ ghi đè development database hoặc release dumps.

```bash
php artisan test tests/Feature/ReleaseArtifactTest.php
bash scripts/prepare-browser-db.sh
npm run test:e2e
```

`ReleaseArtifactTest` dùng MySQL `RefreshDatabase`, so toàn bộ migration files với ledger, kiểm tra seeded coffee/FAQ/Product/admin/bean varieties và request `/coffee`, `/vi/coffee`, `/support`, `/vi/support`. Nó cũng kiểm tra full/schema/data dumps có migration ledger và current schema.

## Generate MySQL artifacts từ fresh disposable schema

Release artifacts phải xuất từ một schema MySQL mới, không dump database `anduongiti` thật và không đưa runtime `cache`/`sessions` vào release. Quy trình đã verify dùng đúng ba disposable schemas `anduongiti_wf_generate`, `anduongiti_wf_verify_full`, `anduongiti_wf_verify_sda`.

Với ServBay, dùng `/Applications/ServBay/package/mysql/current/bin/mysql` và `mysqldump`. Đặt password root local vào biến môi trường cho phiên chạy, không ghi vào `.env` hoặc file docs:

```bash
export LOCAL_ROOT_PASSWORD='<local-root-password>'
export LOCAL_ADMIN_PASSWORD='<local-only-strong-password>'
export MYSQL_PWD="$LOCAL_ROOT_PASSWORD"
MYSQL=/Applications/ServBay/package/mysql/current/bin/mysql
MYSQLDUMP=/Applications/ServBay/package/mysql/current/bin/mysqldump

cleanup_disposable_schemas() {
  $MYSQL -h127.0.0.1 -P3306 -uroot -e "DROP DATABASE IF EXISTS anduongiti_wf_generate; DROP DATABASE IF EXISTS anduongiti_wf_verify_full; DROP DATABASE IF EXISTS anduongiti_wf_verify_sda;"
}
trap cleanup_disposable_schemas EXIT

$MYSQL -h127.0.0.1 -P3306 -uroot -e "DROP DATABASE IF EXISTS anduongiti_wf_generate; DROP DATABASE IF EXISTS anduongiti_wf_verify_full; DROP DATABASE IF EXISTS anduongiti_wf_verify_sda; CREATE DATABASE anduongiti_wf_generate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE anduongiti_wf_verify_full CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE anduongiti_wf_verify_sda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_DATABASE=anduongiti_wf_generate DB_USERNAME=root \
DB_PASSWORD="$LOCAL_ROOT_PASSWORD" \
ADMIN_EMAIL=admin@anduongiti.com ADMIN_PASSWORD="$LOCAL_ADMIN_PASSWORD" \
php artisan migrate:fresh --seed --force
```

Regenerate cả ba file từ `anduongiti_wf_generate`, giữ flags và conventions của dump:

```bash
$MYSQLDUMP -h127.0.0.1 -P3306 -uroot \
  --single-transaction --skip-comments --no-tablespaces \
  --add-drop-table --default-character-set=utf8mb4 \
  anduongiti_wf_generate > database/anduongiti.sql

$MYSQLDUMP -h127.0.0.1 -P3306 -uroot \
  --single-transaction --skip-comments --no-tablespaces \
  --add-drop-table --default-character-set=utf8mb4 --no-data \
  anduongiti_wf_generate > database/anduongiti-schema.sql

$MYSQLDUMP -h127.0.0.1 -P3306 -uroot \
  --single-transaction --skip-comments --no-tablespaces \
  --add-drop-table --default-character-set=utf8mb4 --no-create-info \
  anduongiti_wf_generate > database/anduongiti-data.sql
```

### Import verification bắt buộc

```bash
$MYSQL -h127.0.0.1 -P3306 -uroot --default-character-set=utf8mb4 \
  anduongiti_wf_verify_full < database/anduongiti.sql
$MYSQL -h127.0.0.1 -P3306 -uroot --default-character-set=utf8mb4 \
  anduongiti_wf_verify_sda < database/anduongiti-schema.sql
$MYSQL -h127.0.0.1 -P3306 -uroot --default-character-set=utf8mb4 \
  anduongiti_wf_verify_sda < database/anduongiti-data.sql
```

Với **từng** verification schema, đặt `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD="$LOCAL_ROOT_PASSWORD"` và `DB_DATABASE` tương ứng rồi chạy:

```bash
php artisan migrate:status --no-ansi
php artisan tinker
```

`migrate:status` phải có 12/12 migration `Ran`, không `Pending`. Business checklist trong tinker phải xác nhận: 16 visible coffee rows với đúng 8 slugs mỗi locale; 12 visible FAQ rows với đúng 6 categories mỗi locale; Product `coffee` + `rubber` ở cả EN/VI; admin email và `Hash::check(getenv('LOCAL_ADMIN_PASSWORD'), $user->password)`; đúng `bean_variety` của hai bean SKU ở cả hai locales; có `leads.source_slug` và `coffee_products.bean_variety`. So migration+batch ledger và exact row counts của mọi bảng giữa generation với từng verification schema; full dump và schema+data phải tương đương.

Trap cleanup drop cả ba disposable schemas kể cả khi có lỗi. Sau command, query `INFORMATION_SCHEMA.SCHEMATA` để chứng minh không còn schema tạm. Không thay tên disposable schema bằng database thật.

Full dump và cặp schema+data đều giữ migration ledger. Schema verification so migration+batch ledger và exact row counts giữa generation với từng verification schema; không có SQLite snapshot hoặc cross-engine comparison trong project.
