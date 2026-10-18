# Future Changes — The Safe Workflow

Websites break when changes go straight to the live store. This page describes the only safe way to change MONRICX. Everyone involved — owner and developer — should follow it.

## The golden rule

> **Staging first, production second. Backup always. No shortcuts.**

## Step-by-step workflow

### 1. Make and test changes on staging first

- All new work (design tweaks, new features, text changes) is built and tested on the **staging** site: `https://lemonchiffon-kangaroo-819794.hostingersite.com`
- Click through the changed pages as a customer would. Test on a phone as well as a computer.
- Confirm the staging admin panel still works: products, orders, discount codes.

### 2. Review, then commit to GitHub

- The developer reviews every change before it is saved to GitHub.
- Changes are committed to the `main` branch **only after review**.
- Each commit should have a clear message describing what changed (for example, "Add free-shipping banner to homepage").

### 3. Create a production backup before deployment

Before **every** release to the live site:

- Back up the production **files**.
- Back up the production **database**.
- Store the backup where you can find it again, labelled with the date.
- Confirm the backup is complete before continuing.

See `deployment-runbook.md` for the exact backup steps.

### 4. Deploy using the safe process

Follow `deployment-runbook.md` exactly. In particular:

- **Never use `rsync --delete`.** A past incident wiped the live site for an hour because `--delete` removed files the code package doesn't include. The runbook uses a safe sync that protects server-only files.
- **Never overwrite** these on the server: `.env` (secrets), `storage/` (uploads and caches), `vendor/` (libraries), `public_html` (the live web folder), or existing backups.
- Run database **migrations only when required** (the developer will tell you when a release needs one — most small changes don't).
- **Clear and rebuild Laravel caches** after deploying files.
- **Copy public assets** to `public_html` as described in the runbook.
- **Verify** afterwards: homepage loads, `https://monricx.com/up` shows OK, and `https://monricx.com/admin/login` opens.

### 5. Keep rollback backups

- Keep the pre-release backup until the new version has been confirmed working for at least a day.
- If anything goes wrong, the rollback procedure in `deployment-runbook.md` restores the backup.
- Never delete the most recent known-good backup.

## Things you must never do

| Never do this | Why |
|---|---|
| Paste passwords, API keys, or secrets into chat, email, or GitHub | They can be stolen; secrets live only in the server `.env` and the password manager |
| Edit files directly on the production server | Changes get lost on the next deploy and can break the site instantly |
| Run `rsync --delete` | It deletes server-only files and takes the site down |
| Delete anything from `database/migrations/` | The app tracks which database changes have run; deleting history corrupts it |
| Test with real money on production | Use staging + Razorpay test mode |
| Deploy on a Friday evening or before a sale | If something breaks, no one is around to fix it |

## Before changing production — checklist

- [ ] Change tested and working on **staging**
- [ ] Developer has **reviewed** the change
- [ ] Change **committed** to GitHub `main` with a clear message
- [ ] **Production backup** taken (files + database) and verified
- [ ] You know the exact **commit** being deployed
- [ ] **Rollback plan** ready (backup location noted)
- [ ] No **secrets** shared in chat, email, or GitHub
- [ ] Deployment follows `deployment-runbook.md` — no shortcuts
- [ ] Post-deploy checks pass: homepage, `/up`, `/admin/login`

## Who to contact

| Role | Name | Contact |
|---|---|---|
| Store owner (approves releases) | _(your name)_ | _(your phone/email)_ |
| Developer (Codex — builds and deploys) | _(name)_ | _(phone/email)_ |
| Hosting support | Hostinger | Hostinger control panel → Help |

---
*Related: [deployment-runbook.md](deployment-runbook.md) · [database-guide.md](database-guide.md) · [access-and-secrets.md](access-and-secrets.md)*
