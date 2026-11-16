# anduongiti.com

Marketing & lead-gen site for **An Duong International Trade & Investment Co., Ltd**
(`anduongiti.com`) — a Vietnamese agricultural exporter (instant-coffee
OEM/ODM + natural rubber). Targets buyers across the United Kingdom,
Europe and Southeast Asia.

## Stack

- **Laravel 13** + PHP 8.3
- **Filament 4** admin panel at `/admin`
- **Tailwind v4** with brand tokens
- **MySQL only** for local development, tests, browser tests, and production via the same Eloquent layer
- `mcamara/laravel-localization` for EN (default) + VI (`/vi/...`) routing
- `league/commonmark` for blog post bodies
- `bepsvpt/secure-headers` for CSP / HSTS / X-Frame-Options / Permissions-Policy

## Features

- Public site: home, about, products (coffee + rubber), sustainability,
  partners, news (bilingual blog), contact form
- **Coffee catalog** (`/coffee` + `/vi/coffee`): DB-driven coffee products
  (row-per-locale), category groups, product detail with JSON-LD schema,
  quote CTA deep-linking into the contact form (`?product=<slug>`)
- **Support page** (`/support` + `/vi/support`): support channels from
  settings + DB-driven FAQ accordion (row-per-locale)
- **Social presence**: Facebook / LinkedIn / Shopee / Alibaba footer icons,
  marketplace cards on contact, floating Zalo / WhatsApp / phone / email
  buttons (all settings-driven, link-only — CSP-safe)
- **Lead pipeline**: email notification on new contact submissions
  (`NewLeadMail`, failure-tolerant), validation errors rendered in the
  contact form
- Filament admin: posts, products, coffee products (image upload),
  partners, leads, FAQs, Settings page (contact + social keys)
- Accurate Vietnam SVG map (Natural Earth 50m, 8 polygons inc. Phú Quốc,
  Côn Đảo, Cát Bà; Hoàng Sa & Trường Sa archipelagos labelled)
- Responsive (mobile + desktop)
- Dynamic sitemap + robots.txt
- PDF Company Profile downloadable from About / Contact / Footer

## Local development

The project is **MySQL-only**. Create the local database and user, then configure `.env` from `.env.example`:

```bash
git clone git@github.com:d4rkside9196/anduongiti-laravel.git
cd anduongiti-laravel
composer install
cp .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS anduongiti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed --force
npm ci && npm run build
php artisan serve            # → http://localhost:8000
```

Set `ADMIN_EMAIL` and a unique `ADMIN_PASSWORD` when seeding a disposable/local database, or use the admin password-reset flow. **Use a unique password and change it before going live.**

If you don't have PHP / Composer locally, use the Docker wrapper —
see `.serena/memories/suggested_commands.md` for the exact image and
helper script.

### Testing and browser databases

PHPUnit uses the MySQL `anduongiti_test` database. Browser tests recreate the disposable MySQL `anduongiti_browser` database via `scripts/prepare-browser-db.sh`; the development database and SQL release dumps are never modified by browser tests.

```bash
php artisan test
npm run test:e2e
npm run test:a11y
```

`ReleaseArtifactTest` verifies the MySQL migration ledger, seeded business data, current schema, and catalog/support routes. SQL release artifacts and import verification live in [`database/README.md`](database/README.md). Do not use a schema-only + `db:seed` deployment workflow.

## Deploy

Hostinger (Premium / Business / Cloud / VPS — anything with PHP 8.3 +
MySQL): see [`DEPLOY-HOSTINGER.md`](./DEPLOY-HOSTINGER.md).

Database imports for one-shot setup live in
[`database/anduongiti.sql`](./database/) — see the README in that folder.

## Security

This site is hardened with:

- Laravel CSRF (auto on every form)
- bcrypt-hashed passwords, Filament `canAccessPanel` gate, email-verified check
- Rate limiting: 10 contact-form posts / IP / hour, 5 login attempts / minute
- Open-redirect protection (named-route redirects only)
- HTTPS forced + HSTS in production, trusted reverse proxies
- Strict CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy
- Markdown rendered with HTML-escape + safe-links allowlist
- Eloquent parameterised queries, `$fillable` allowlist on every model
- Magic-byte sniffing + size cap on image uploads (Filament default)

For the full security checklist + things deliberately NOT included
(2FA, IP allowlist, audit log), see the
[`qa_baseline`](.serena/memories/qa_baseline.md) Serena memory.

## Project layout

```
app/
  Filament/
    Pages/ManageSettings  /admin/settings — contact + social keys
    Resources/            CRUD for Posts, Products, CoffeeProducts, Partners, Faqs, Leads
  Http/Controllers/       Home, Page, Product, Coffee, News, Lead, Sitemap
  Mail/                   NewLeadMail (contact notification)
  Models/                 Post, Product, CoffeeProduct, Partner, Faq, Setting, Lead, User
config/
  site.php               brand info, nav, profile_pdf
  vn_map.php             Vietnam outline path data (Natural Earth)
  laravellocalization.php
  secure-headers.php
database/
  migrations/            posts, products, coffee_products, faqs, partners, settings, leads
  seeders/               idempotent seed (admin user + sample content)
  anduongiti.sql         pre-built MySQL dump for Hostinger phpMyAdmin import
lang/{en,vi}/            UI strings
resources/
  css/app.css            Tailwind tokens + utility classes
  views/
    components/
      layouts/site       public site shell (the <x-layouts.site> Blade component)
      site/              Hero, CoffeeShowcase, RubberStrip, SupplyMap,
                         Stats, Certifications, Process, DownloadCard,
                         FloatingContact, ...
    pages/               home, about, products, product-detail, coffee,
                         coffee-product, support, sustainability, partners,
                         news, news-post, contact
    errors/404.blade.php
public/
  downloads/             Company Profile PDF + cover thumbnail
  uploads/coffee/        demo images + SVG fallbacks (gitignored)
  build/                 Vite output (committed in this repo)
routes/
  web.php                public + sitemap routes
```

## Useful commands

```bash
php artisan migrate:fresh --seed   # reset DB
php artisan db:seed                 # re-seed (idempotent)
php artisan route:list              # all routes
php artisan view:clear              # nuke Blade cache
npm run build                       # compile CSS/JS for production
```

---

Built for **An Duong International Trade & Investment Co., Ltd** (Hà Nội).
