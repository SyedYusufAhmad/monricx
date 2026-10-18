# MONRICX — Client Handover

Welcome. This folder contains everything needed to run the MONRICX online store day to day. It is written for non-technical readers. If a step ever feels risky, stop and ask your developer before continuing.

## What this project is

MONRICX (`https://monricx.com`) is an online jewellery store built with **Laravel** (PHP) and **MySQL**, hosted on **Hostinger**. Customers browse products, add them to a cart, and check out with online payment (Razorpay) or Cash on Delivery (COD). Store owners manage everything from a private admin panel.

## Important web addresses

| What | Address |
|---|---|
| Production store (live, customers use this) | `https://monricx.com` |
| Staging store (testing only, customers never use this) | `https://lemonchiffon-kangaroo-819794.hostingersite.com` |
| Admin login (production) | `https://monricx.com/admin/login` |
| Admin login (staging) | `https://lemonchiffon-kangaroo-819794.hostingersite.com/admin/login` |
| Health check | `https://monricx.com/up` |

> **Warning — the two sites look identical but are completely separate.** Staging is a safe playground for testing changes. Production is the real store where customers shop and pay. **Never test on production. Never deploy staging content directly to production without following the release process in `future-changes.md`.** Deploying to the wrong domain can break the live store or mix up real customer orders with test data.

## Staging vs production

| | Staging | Production |
|---|---|---|
| Purpose | Testing changes safely | Real customers and real money |
| URL | `lemonchiffon-kangaroo-819794.hostingersite.com` | `monricx.com` |
| Database | Separate test data | Real orders, customers, payments |
| Razorpay | Test mode (no real money) | Live mode (real money) |
| Who uses it | Developer and store owner for checks | Customers |

Rule of thumb: **if real money or real customer data is involved, you are on production — be extra careful.**

## Technology at a glance

- **Framework:** Laravel (PHP **8.4 or newer is required** — the site will not run on older PHP)
- **Database:** MySQL (on Hostinger)
- **Hosting:** Hostinger Business plan
- **Payments:** Razorpay (live mode on production, test mode on staging)
- **SSL:** Active — both `https://monricx.com` and `https://www.monricx.com` work

## Important folders (on the server)

Production files live in `/home/u764279061/domains/monricx.com`:

| Folder | What it holds | Can you touch it? |
|---|---|---|
| `app/` | The application's code | No — developer only |
| `resources/views/` | Page templates | No — developer only |
| `routes/` | URL definitions | No — developer only |
| `database/migrations/` | Database change history — **never delete these** | No |
| `storage/` | Uploaded images, caches, logs | No — the app manages this |
| `public/` | Public files (images, CSS, JS) | No — updated during deployment |
| `.env` | Secret configuration (passwords, API keys) | **Never** — see `access-and-secrets.md` |
| `vendor/` | Third-party code libraries | **Never** — reinstalled by the developer |

In short: day-to-day store management happens in the **admin panel**, not in these folders. If someone asks you to edit files on the server directly, check with your developer first.

## Documents in this folder

1. [README.md](README.md) — this overview (you are here)
2. [admin-guide.md](admin-guide.md) — how to use the admin panel: products, orders, refunds, discount codes
3. [future-changes.md](future-changes.md) — the correct workflow for making changes safely
4. [deployment-runbook.md](deployment-runbook.md) — step-by-step safe deployment (developer use)
5. [razorpay-guide.md](razorpay-guide.md) — payments, webhooks, and refunds
6. [database-guide.md](database-guide.md) — database safety rules
7. [access-and-secrets.md](access-and-secrets.md) — checklist of accounts and credentials (placeholders only)
8. [change-log.md](change-log.md) — major milestones delivered so far

## Before changing production — checklist

Complete every item before anyone touches the live site:

- [ ] The change has been tested on **staging** first and works correctly
- [ ] A **backup** of production has been taken (files + database)
- [ ] You know exactly which GitHub commit is being deployed
- [ ] The developer has reviewed and approved the release
- [ ] You have a **rollback plan** (how to restore the backup if something goes wrong)
- [ ] No passwords or secrets are being pasted into chat, email, or GitHub

## Who to contact

| Role | Name | Contact |
|---|---|---|
| Store owner | _(your name)_ | _(your phone/email)_ |
| Developer (Codex) | _(name)_ | _(phone/email)_ |
| Hosting support | Hostinger | Hostinger control panel → Help |
| Payment support | Razorpay | Razorpay dashboard → Support |

---
*No passwords, API secrets, tokens, or private keys are stored in this documentation or in the repository.*
