# Deploy Laravel app lên Hostinger

> Đây là **Laravel 13 + Filament 4 + MySQL-only** app.
> Plan Hostinger: **Premium / Business / Cloud / VPS** (cần PHP 8.3+ và composer).

## Hardening checklist (chạy trước khi go-live)

Repo không chứa SQLite snapshot hay credentials production. Trên production, **bắt buộc** làm 3 bước này trước khi mở public:

1. **Tạo APP_KEY riêng trên server — bắt buộc, bất kể `.env` đã có key hay chưa** (git history từng chứa một key dev đã public, và clone từ repo sẽ kéo theo `.env` có sẵn key đó):
   ```bash
   php artisan key:generate --force  # --force ghi đè key hiện có mà không hỏi
   php artisan optimize:clear        # xoá cache để key mới có hiệu lực
   ```
   Không dùng key local/CI cho production. Rotate key sẽ invalidate các session đã ký bằng key cũ.

2. **Thiết lập tài khoản admin thật và đổi credential trước go-live.** Seeder không dùng public default password: thiếu `ADMIN_PASSWORD` sẽ tạo random credential không dùng để đăng nhập.

3. **Verify cookie flags trên prod** — `curl -sI https://anduongiti.com/ | grep -i set-cookie` phải thấy `Secure; HttpOnly; SameSite=Lax`. Nếu thiếu, kiểm tra `.env` có:
   ```
   SESSION_SECURE_COOKIE=true
   SESSION_ENCRYPT=true
   SESSION_HTTP_ONLY=true
   SESSION_SAME_SITE=lax
   APP_DEBUG=false
   APP_ENV=production
   ```

4. **Verify proxy/CDN trước khi nới `trustProxies()`** — mặc định `bootstrap/app.php` chỉ tin các dải private (`127/8`, `10/8`, `172.16/12`, `192.168/16`). Shared hosting Hostinger không đặt reverse proxy trước Apache ở cấu hình mặc định (REMOTE_ADDR là IP client thật). Chỉ khi bật CDN (Cloudflare qua hPanel) hoặc lớp proxy khác thì ingress mới nối origin bằng IP của nó — khi đó xác định CIDR ingress (so sánh `REMOTE_ADDR` vs `X-Forwarded-For` trong access log trên server) và thêm CIDR đó vào `trustProxies(at: [...])`. Không bật proxy Cloudflare (mây cam) khi chưa thêm CIDR: toàn bộ IP trong audit log/throttle key sẽ là IP edge thay vì IP client.

---

## Bước 1 — Tạo MySQL DB trong hPanel

1. Sidebar → **Databases → MySQL Databases** → Create new.
2. Lưu lại: `database name`, `user`, `password`, `host` (thường là `localhost`).

## Bước 2 — Upload code

**Option A — Git (recommended):**
```bash
cd ~/domains/anduongiti.com/public_html
git clone <your-repo> .
```

**Option B — Zip upload:**
1. Local: `tar -czf release.tgz --exclude=node_modules --exclude=vendor --exclude='.env' --exclude='*.sqlite' .`
2. Upload qua File Manager → Extract.

## Bước 3 — Cấu hình .env

```bash
cp .env.example .env
nano .env
```

Set:
```
APP_NAME="An Duong ITI"
APP_ENV=production
APP_KEY=        # generated below
APP_DEBUG=false
APP_URL=https://anduongiti.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456_anduongiti
DB_USERNAME=u123456_admin
DB_PASSWORD=YourStrongPassword

ADMIN_EMAIL=admin@anduongiti.com
ADMIN_PASSWORD= # chỉ dùng một secret mạnh khi seed lần đầu; không commit giá trị

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@anduongiti.com
MAIL_PASSWORD=YourMailboxPassword
MAIL_FROM_ADDRESS=no-reply@anduongiti.com
MAIL_FROM_NAME="${APP_NAME}"
```

> **Bắt buộc set MAIL_*.** Template `.env.example` mặc định `MAIL_MAILER=log`
> (ghi mail vào log thay vì gửi), và app sẽ từ chối boot trong production khi
> mailer vẫn là `log` (xem `AppServiceProvider`). Thiếu SMTP thì email reset
> password/invite không bao giờ tới tay.

## Bước 4 — Cài dependencies + import database + setup

Fresh install dùng full MySQL dump đã có sẵn schema, seeded content và migration ledger:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
mysql -u <DB_USERNAME> -p <DB_DATABASE> < database/anduongiti.sql
php artisan migrate:status
php artisan config:cache
php artisan route:clear
php artisan view:cache

# Build frontend assets
npm ci
npm run build
```

`migrate:status` phải báo tất cả migration là `Ran`, không có `Pending`. Có thể thay full dump bằng hai lệnh import `anduongiti-schema.sql` rồi `anduongiti-data.sql` vào database trống; cặp này chứa migration ledger. Routes được đăng ký với locale prefix động nên deploy giữ routes ở dạng không cache và dùng `php artisan route:clear`.

**Không import schema-only rồi chạy `php artisan db:seed`.** Ledger rỗng khiến lần migrate tiếp theo cố tạo lại bảng. `anduongiti-schema.sql` chỉ dùng một mình để phân tích schema; fresh install phải dùng full dump hoặc cặp schema+data. Chi tiết artifacts và quy trình regenerate trên disposable MySQL schemas nằm trong [`database/README.md`](database/README.md).

## Bước 5 — Hostinger Apache config

Repo đã ship sẵn `.htaccess` ở root + `public/.htaccess` mặc định của
Laravel. Khi extract tarball vào `~/public_html/` thì Apache đã có thể
forward request vào `public/index.php` ngay.

**Nếu vẫn bị 403** sau khi upload:

1. Verify `.htaccess` ở root tồn tại — `ls -la ~/public_html/.htaccess`.
   Nếu thiếu (do FTP client bỏ qua dotfile), tạo lại với nội dung:

   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteCond %{REQUEST_FILENAME} -f
       RewriteRule ^ - [L]
       RewriteRule ^(.*)$ public/$1 [L]
   </IfModule>
   ```

2. Hoặc cleanest path: vào hPanel → Domain → set **Document Root** =
   `~/domains/anduongiti.com/public_html/public` (Laravel khuyên cách này).
   Khi đó xoá luôn root `.htaccess`, chỉ còn `public/.htaccess` là đủ.

3. Đảm bảo Hostinger PHP version đặt ở **8.3+** (hPanel → Advanced →
   PHP Configuration).

4. Đảm bảo `mod_rewrite` bật (Hostinger default đều bật).

5. Quyền folder: `chmod -R 755 .` rồi `chmod -R 775 storage bootstrap/cache`.

## Bước 6 — Truy cập

- Site công khai: `https://anduongiti.com`
- Admin Filament: `https://anduongiti.com/admin`

Dùng tài khoản thật đã được cấp qua quy trình credential handover; không có public default password. `ADMIN_EMAIL` / `ADMIN_PASSWORD` trong `.env` chỉ dùng khi chạy seeder lần đầu, không ghi đè user đã có trong full dump.

## Update lần sau

Trước update phải backup cả files (bao gồm `.env` được lưu riêng, protected) và MySQL, rồi thử restore vào vị trí/schema riêng. Rehearsal upgrade phải bắt đầu từ file tree và `database/anduongiti.sql` của previous release thật; không rollback current dump để giả previous state. Dùng `docs/superpowers/runbooks/production-release.md` và `rollback.md`.

```bash
cd ~/domains/anduongiti.com/public_html
git pull   # hoặc upload zip mới
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan migrate:status
npm ci && npm run build
php artisan config:cache && php artisan route:clear && php artisan view:cache
```

## Backup MySQL

```bash
mysqldump -u user -p database_name > backup-$(date +%Y%m%d).sql
```

Hoặc dùng phpMyAdmin trong hPanel → Export.

## Troubleshooting

| Lỗi | Fix |
|---|---|
| 500 trắng trang | `cat storage/logs/laravel.log` xem chi tiết |
| Permission denied storage | `chmod -R 775 storage bootstrap/cache` |
| Filament login redirect loop | `php artisan optimize:clear` |
| CSS không load | `npm run build` chưa chạy hoặc `APP_URL` sai |

---

## Pricing comparison

| Plan | Đủ chạy app này không |
|---|---|
| Hostinger **Premium** ($3/tháng) | ✅ Đủ — 1 website, 100 GB SSD, MySQL, Composer |
| Hostinger **Business** ($4/tháng) | ✅ Đủ + daily backup |
| Hostinger **Cloud Startup** ($10/tháng) | ✅ Tốt nhất — dedicated resources, SSL ngon |
| Hostinger **VPS** ($5+/tháng) | ✅ Full control, có thể `ssh` thoải mái |

Bạn có thể bắt đầu với **Premium $3/tháng** — đủ cho site B2B traffic vài nghìn visit/tháng.
