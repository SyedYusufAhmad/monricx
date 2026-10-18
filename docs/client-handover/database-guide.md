# Database Guide — MySQL on Hostinger

## The basics

- The store's data (products, orders, customers, payments) lives in a **MySQL** database on Hostinger.
- Production and staging each have their **own separate database** — they never share data.
- The application connects using credentials stored in the server's `.env` file.

## Safety rules

### Never put passwords in documentation or GitHub

Database passwords must **never** appear in:

- These documents
- GitHub (code, issues, pull requests, wikis)
- Chat, email, or screenshots

The password lives in two places only: the Hostinger control panel and the server `.env` file, both managed by the developer.

### Always back up before migrations

A **migration** is a controlled change to the database structure (for example, adding the SKU column to products, or the refund-tracking columns to payments). Before running **any** migration on production:

1. Take a full database backup (see below).
2. Confirm the backup is complete.
3. Only then run the migration.
4. Verify the site still works afterwards.

Most routine releases don't need a migration at all — the developer will tell you when one is required.

### Existing products have nullable SKU values

The `sku` column on products is **optional** (nullable). Older products created before SKU support was added simply have an empty SKU — this is normal and causes no problems. The admin panel shows a dash (`—`) for them.

### Migration history must not be deleted

The `database/migrations/` folder in the code is the official record of every structural change, in order. **Never delete or rename these files.** Laravel tracks which migrations have already run; deleting history makes the tracker unreliable and can corrupt the database on the next deploy.

### How to check migration status safely

This command is **read-only** — it only reports, it changes nothing:

```bash
/opt/alt/php84/usr/bin/php artisan migrate:status
```

Run it over SSH from the production directory. It lists each migration and whether it has run. Share its output with the developer if anything looks unexpected.

## Backups

### Taking a backup

The developer takes a MySQL dump before every production release (see `deployment-runbook.md`). As a store owner, your job is to make sure this step is **never skipped** — ask for confirmation before approving any release.

### How to restore from a backup

If the database needs to be restored:

1. **Stop.** Do not run further commands on production.
2. Contact the developer immediately.
3. The developer will coordinate with **Hostinger support** if needed — Hostinger can also restore from their own automated backups.
4. After restoring, verify the store: homepage, a product page, and the admin login.

Database restores should only be done by the developer, ideally with Hostinger support on standby.

## Before changing production — checklist

- [ ] Database backup taken and verified before any migration
- [ ] Migration is required by this release (developer confirmed)
- [ ] No passwords shared in chat, email, GitHub, or screenshots
- [ ] `migrate:status` checked after the migration
- [ ] Site verified working after the migration

## Who to contact

| Role | Name | Contact |
|---|---|---|
| Store owner | _(your name)_ | _(your phone/email)_ |
| Developer (Codex — database changes) | _(name)_ | _(phone/email)_ |
| Hosting/database support | Hostinger | Hostinger control panel → Help |

---
*Related: [deployment-runbook.md](deployment-runbook.md) · [future-changes.md](future-changes.md) · [access-and-secrets.md](access-and-secrets.md)*
