# AICAP Furniture BOS

Multi-tenant SaaS for furniture brands. Each licensed company gets its own
subdomain (`{slug}.aicap.my`) with its own catalog, vouchers, branding, and
analytics. HQ controls everything from a central super-admin panel.

## Stack
- PHP 8 (procedural, no framework)
- MySQL 5.7+ / 8.0
- Apache (Hostinger-compatible)
- Vanilla CSS, no JS framework

## Layout
```
/                        public site (tenant-aware via subdomain)
  index.php              HQ landing OR company homepage
  catalog.php            product list
  product.php            product detail
  voucher.php            voucher claim
  member-login.php       member auth
  member-register.php
  member-dashboard.php
  whatsapp-redirect.php  logs lead + redirects to wa.me
  q.php                  campaign QR redirect (rewritten from /q/{co}/{key})
/admin/                  Super Admin (HQ)
/company-admin/          Company Admin
/inc/                    shared includes (config, db, auth, csrf, layout, ...)
/sql/schema.sql          full schema
/uploads/                user-uploaded images (organized per company)
install.php              one-time installer (delete after use)
```

## Install

1. Create a MySQL database, e.g. `furniture_bos`.
2. Edit `inc/config.php` with your DB credentials. On Hostinger you can
   alternatively set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` as env vars.
3. Upload the entire repo to your `public_html/`.
4. Visit `https://aicap.my/install.php` — it will:
   - apply `sql/schema.sql`
   - create a default super admin
   - create a sample company with a default company admin
5. **Delete `install.php`** after you see the success page.

Default credentials (change immediately):

| Role          | URL                            | Email              | Password      |
| ------------- | ------------------------------ | ------------------ | ------------- |
| Super Admin   | `/admin/login.php`             | `admin@aicap.my`   | `Admin@12345` |
| Company Admin | `/company-admin/login.php`     | `owner@ladore.my`  | `Owner@12345` |

## DNS

Point `*.aicap.my` (wildcard) to the same Apache vhost as `aicap.my`. Tenant
resolution happens in `inc/tenant.php` based on the host header. Custom
domains can be wired up per-company by setting `companies.custom_domain`.

## Multi-tenant rules

- Every tenant table includes `company_id`.
- Every query is scoped: `WHERE company_id = ?`.
- Tenant id is derived from:
  - Public site → subdomain / custom_domain (`current_company_id()`).
  - Company admin → session (`company_admin()['company_id']` then `$CID`).

## Tracked events

`analytics_events.event_type` values used by the app:
- `page_view`
- `product_view`
- `whatsapp_click`
- `voucher_claim`
- `campaign_scan`

Leads (`leads.source`):
- `whatsapp_click`
- `voucher_claim`
- `campaign_scan`
- `contact_form`
- `other`

## Security

- Prepared statements everywhere (PDO).
- Bcrypt password hashes.
- CSRF tokens on every POST form.
- HttpOnly + SameSite cookies.
- `/inc` and `/sql` denied via `.htaccess`.
- `/uploads` blocks PHP execution.

## Roadmap (Phase 2+)

- QR image generation server-side
- Block-renderer for `company_pages` (front-end)
- Per-company custom CSS
- Email + WhatsApp notifications
- Voucher redemption staff app
- Per-company dashboard charts
