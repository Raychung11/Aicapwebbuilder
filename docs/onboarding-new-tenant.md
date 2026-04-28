# Onboarding a new tenant on AICAP

This is the runbook for adding a new furniture company (tenant) to the AICAP
platform on Hostinger. Repeat these steps for every new brand.

> Estimated time: **15–30 minutes** end-to-end (most of it waiting for SSL).

---

## What happens in an onboarding

| Layer | What you do |
|---|---|
| **Database** | Add a `companies` row (name, slug, subdomain, branding, etc.) and a `company_admins` row so the tenant can log in. |
| **Hostinger** | Create the matching subdomain pointing to the same `public_html` and install SSL. |
| **App** | Optionally seed a starter catalog and hand the login over to the tenant. |

The slug you choose **must match** the subdomain you create in Hostinger.
For example: company `slug = 'ladaza'` ↔ Hostinger subdomain `ladaza.aicap.my`.

---

## Step 1 — Add the company in Super Admin

1. Sign in at <https://aicap.my/admin/login.php>.
2. **Companies → + Add Company**.
3. Fill in:
   - **Name** — public brand name, e.g. *Ladore Signature*
   - **Slug** — used in `/q/<slug>/...` campaign URLs, e.g. `ladoresignature`
   - **Subdomain** — must match what you'll create in Hostinger, e.g. `ladoresignature`
   - **Theme Color** / **Secondary Color** — the brand palette
   - **Description**, **Phone**, **Email**, **WhatsApp Number**
4. Click **Save**.

> 🪑 Tip — after saving, click the **Seed** button on `/admin/companies.php` to drop
> 16 sample furniture items + 2 vouchers into the new tenant for an instant demo.

---

## Step 2 — Create the company admin login

The tenant needs a `company_admins` account so they can log in at
`/company-admin/login.php`.

> Today this is done via SQL. A UI is on the roadmap — see *Future improvements*
> at the bottom.

In **hPanel → Databases → phpMyAdmin** (or any MySQL client) on the AICAP
database, run:

```sql
-- Use the company id you just created (Companies page shows it in the URL)
SET @cid := 5;
SET @email := 'owner@ladaza.my';
SET @name  := 'Ladaza Owner';
-- Generate a bcrypt hash with PHP first (see below) and paste it in:
SET @hash  := '$2y$10$REPLACE_WITH_GENERATED_HASH';

INSERT INTO company_admins (company_id, name, email, password_hash, role)
VALUES (@cid, @name, @email, @hash, 'owner');
```

To generate the bcrypt hash, run this in **hPanel → Advanced → SSH Access**
or any local PHP shell:

```bash
php -r "echo password_hash('YourTempPassword123!', PASSWORD_BCRYPT, ['cost' => 10]), \"\n\";"
```

Send the tenant the email + temporary password and tell them to reset it via
`/company-admin/forgot-password.php` on first login.

---

## Step 3 — Create the subdomain in Hostinger

1. **hPanel → Domains → aicap.my → Subdomains**.
2. **Create a New Subdomain**:
   - **Subdomain** — type the slug, e.g. `ladaza`
   - ✅ Tick **"Custom folder for subdomain"**
   - **Path** — type `public_html`
   - Click **Create**.

This makes the subdomain share the same docroot as the main domain, so the
PHP host-header routing in `inc/tenant.php` does the rest.

### Plan B — if Hostinger won't let two domains share `public_html`

If you get a *"Folder is in use"* error, accept the per-subdomain folder
(e.g. `public_html/ladaza/`) and put two files in it:

**`public_html/ladaza/index.php`**
```php
<?php require __DIR__ . '/../index.php';
```

**`public_html/ladaza/.htaccess`**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [L]
```

This forwards every request to the main app, which still picks up the
correct tenant from the host header.

---

## Step 4 — Install SSL for the new subdomain

1. **hPanel → SSL** (or **Security → SSL/TLS**).
2. Find `ladaza.aicap.my` in the list.
3. Click **Install** (Let's Encrypt). Provisioning takes 5–30 minutes.
4. Once installed, enable **Force HTTPS**.

If you have a wildcard certificate for `*.aicap.my` on a Business / Cloud
plan, it covers every new subdomain automatically — skip this step.

---

## Step 5 — Verify

From your laptop:

```bash
nslookup ladaza.aicap.my          # should return an IP
curl -I https://ladaza.aicap.my/  # should return 200 or 302
```

Then open `https://ladaza.aicap.my/` in a browser. You should see the
tenant homepage with the new brand's colors, name and (if seeded) products.

While you wait for SSL to provision, the apex preview still works:

```
https://aicap.my/?as=ladaza
```

---

## Step 6 — Hand over to the tenant

Send the tenant a short email with:

- Their site URL: `https://ladaza.aicap.my`
- Tenant admin URL: `https://aicap.my/company-admin/login.php`
- Their email + temporary password
- A pointer to **forgot-password** if they need to reset:
  `https://aicap.my/company-admin/forgot-password.php`

Suggested first-day checklist for the tenant:

1. Sign in and change password.
2. Upload the company logo (Branding & SEO → Logo).
3. Adjust theme colors and description.
4. Add a branch (with map embed + Waze link) under **Branches**.
5. Replace seeded sample products with real ones (delete the seed first).
6. Set Meta Title and Social Share Image (Branding & SEO).
7. Create one launch voucher under **Vouchers**.

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| `DNS_PROBE_FINISHED_NXDOMAIN` | Subdomain not yet created in Hostinger, or DNS propagation < 15 min | Wait, then `nslookup`. |
| `ERR_SSL_PROTOCOL_ERROR` | SSL cert not installed for the subdomain | hPanel → SSL → Install for that subdomain. |
| Subdomain loads, but shows the **HQ landing** instead of the tenant | The `companies.subdomain` value doesn't match the URL | Edit the company in `/admin/companies.php` and fix the **Subdomain** field. |
| Subdomain loads an empty / 403 page | Subdomain points to its own folder, not the shared `public_html` | Either recreate with custom folder = `public_html`, or apply Plan B from Step 3. |
| Tenant can't sign in at `/company-admin/login.php` | No `company_admins` row exists yet | Re-run the SQL from Step 2, or use forgot-password to set a new one. |
| 500 error after onboarding | Schema not migrated for new columns | Open `/install.php` once (it auto-heals columns), then **delete `install.php`**. |

---

## Quick checklist (one-page)

```
[ ] /admin/companies.php → Add Company (name + slug + subdomain + colors)
[ ] phpMyAdmin → INSERT into company_admins (company_id, email, hash, name)
[ ] /admin/companies.php → 🪑 Seed (optional)
[ ] hPanel → Subdomains → create <slug>, custom folder = public_html
[ ] hPanel → SSL → Install for <slug>.aicap.my
[ ] curl -I https://<slug>.aicap.my/   →  200 or 302
[ ] Email tenant: site URL, admin URL, temp password, reset-password link
```

---

## Future improvements (gaps to close)

- **Auto-create company_admin** when adding a company — replace the SQL step
  with a built-in form on `/admin/company-edit.php`. _(High value; small change.)_
- **Auto-issue invite emails** with a one-time link instead of a temp password.
- **Subdomain availability checker** that pings DNS + the URL to confirm
  Step 3/4 succeeded.
- **Wildcard SSL** on the platform plan so Step 4 disappears entirely.
