# Deployment Runbook — Hostinger (Developer Use)

This is the step-by-step safe deployment process for `https://monricx.com`. It is written for the developer. The store owner should read `future-changes.md` instead — the checklist there must be complete before any step below is run.

> **Warning:** Production serves real customers and real money. Follow every step in order. If any step fails, **stop** and roll back — do not skip ahead.

## Reference

| Item | Value |
|---|---|
| PHP binary (must use this — the default PHP is too old) | `/opt/alt/php84/usr/bin/php` |
| Production directory | `/home/u764279061/domains/monricx.com` |
| Public web folder | `/home/u764279061/domains/monricx.com/public_html` (serves `public/`) |
| Health check | `https://monricx.com/up` |
| Admin login | `https://monricx.com/admin/login` |

Connect over SSH first, then run the commands below from the production directory.

## 1. Take a backup (before every release)

```bash
cd /home/u764279061/domains/monricx.com
```

- Back up the full application directory (excluding `vendor/` is acceptable; it can be reinstalled).
- Back up the production MySQL database (see `database-guide.md`).
- Label the backup with today's date, e.g. `monricx-prod-backup-2026-10-19/`.
- Verify the backup exists and is non-empty before continuing.

**Do not proceed without a verified backup.**

## 2. Download the GitHub release

Download the reviewed commit from GitHub as a tarball (replace `COMMIT_SHA` with the exact commit being deployed):

```bash
cd /tmp
curl -L -o monricx-main.tar.gz https://github.com/SyedYusufAhmad/monricx/archive/COMMIT_SHA.tar.gz
mkdir -p /tmp/monricx-main && tar -xzf monricx-main.tar.gz -C /tmp/monricx-main --strip-components=1
```

## 3. Sync files (safe — protects server-only files)

> **Never use `rsync --delete` without the exclusions below.** A past `--delete` without exclusions wiped `vendor/`, `.env`, and other server-only files and took the site down for an hour.

```bash
rsync -a --delete \
  --exclude='.git' \
  --exclude='.env' \
  --exclude='storage' \
  --exclude='vendor' \
  --exclude='public_html' \
  --exclude='backups' \
  /tmp/monricx-main/ /home/u764279061/domains/monricx.com/
```

What the exclusions protect:

| Excluded | Why it must never be overwritten |
|---|---|
| `.env` | Production secrets (database password, Razorpay keys) |
| `storage/` | Uploaded product images, caches, logs |
| `vendor/` | Installed PHP libraries (the tarball doesn't include them) |
| `public_html` | The live web document root |
| `backups` | Previous rollback backups |
| `.git` | Not needed on the server |

## 4. Reinstall dependencies only if needed

If `vendor/autoload.php` is missing (it should not be, thanks to the exclusion above):

```bash
/opt/alt/php84/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction
```

## 5. Run migrations only when required

Most releases do **not** need a migration. Run one only when the release notes say the database structure changed — and only after the backup in step 1:

```bash
/opt/alt/php84/usr/bin/php artisan migrate --force
```

Check status safely (read-only) with:

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

## 6. Clear and rebuild caches

```bash
/opt/alt/php84/usr/bin/php artisan optimize:clear
/opt/alt/php84/usr/bin/php artisan optimize
```

## 7. Copy public assets to public_html

Hostinger serves the site from `public_html`, so refreshed public files must be copied over:

```bash
rsync -a /home/u764279061/domains/monricx.com/public/ /home/u764279061/domains/monricx.com/public_html/
```

(If `~/public_html` is a symlink to `domains/monricx.com/public`, verify the link is intact instead: `ls -la ~/public_html`.)

## 8. Verify the deployment

Run these checks — all must pass:

```bash
curl -s -o /dev/null -w "homepage: %{http_code}\n" https://monricx.com
curl -s -o /dev/null -w "www: %{http_code}\n" https://www.monricx.com
curl -s https://monricx.com/up
```

- Homepage and `www` homepage: HTTP **200**.
- `/up`: shows the application is healthy.
- Open `https://monricx.com/admin/login` in a browser and confirm the login page loads.

Also place a quick test browse (homepage → product → cart) to confirm the storefront works.

## 9. Clean up

```bash
rm -rf /tmp/monricx-main /tmp/monricx-main.tar.gz
```

Keep the pre-release backup for at least a day after the release is confirmed working.

## Rollback procedure

If the new release is broken:

1. **Stop.** Do not attempt further fixes on production.
2. Restore the application files from the pre-release backup.
3. If a migration ran, restore the database backup as well (see `database-guide.md` — involve Hostinger support if unsure).
4. Re-run `artisan optimize:clear` and `artisan optimize`.
5. Re-copy public assets to `public_html` (step 7).
6. Re-run the verification checks (step 8).
7. Inform the store owner, then diagnose the problem on **staging**, not production.

## Before changing production — checklist

- [ ] Release tested on staging and approved
- [ ] Exact GitHub commit SHA recorded: `________________`
- [ ] Production files backup taken and verified
- [ ] Production database backup taken and verified
- [ ] Rollback backup location noted: `________________`
- [ ] No secrets pasted into chat, email, or GitHub during this release

---
*Related: [future-changes.md](future-changes.md) · [database-guide.md](database-guide.md) · [access-and-secrets.md](access-and-secrets.md)*
