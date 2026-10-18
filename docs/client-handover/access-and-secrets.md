# Access & Secrets — Checklist (Placeholders Only)

> **This document contains no real secrets.** Every value below is a placeholder. Fill in the real values privately — in a password manager, never in this file, in chat, or on GitHub.

## How to use this checklist

- The **store owner** should know where each item is kept (which password manager or secure note).
- The **developer** needs operational access to the marked items to deploy and fix the site.
- Review this list whenever someone joins or leaves the project, and rotate any credentials they had access to.

## Checklist

| # | Item | Where the real value is kept | Who needs it |
|---|---|---|---|
| 1 | Hostinger account owner (email) | `<hostinger-account-email>` | Owner, Developer |
| 2 | Domain registrar / DNS owner | `<registrar-name>` — account `<registrar-account-email>` | Owner, Developer |
| 3 | SSH host | `<ssh-host>` | Developer |
| 4 | SSH port | `<ssh-port>` | Developer |
| 5 | SSH username | `<ssh-username>` | Developer |
| 6 | Production database name | `<production-db-name>` | Developer |
| 7 | Production database username | `<production-db-username>` | Developer |
| 8 | Production database password | _(in server `.env` + password manager only)_ | Developer |
| 9 | Razorpay live Key ID | `<razorpay-live-key-id>` | Developer |
| 10 | Razorpay live Key Secret | _(Razorpay dashboard + server `.env` only)_ | Developer |
| 11 | Razorpay Webhook Secret | _(Razorpay dashboard + server `.env` only)_ | Developer |
| 12 | GitHub repository owner | `<github-username>` (repo: `<github-username>/monricx`) | Owner, Developer |
| 13 | Admin panel credentials (production) | _(password manager only — or use Temporary admin access)_ | Owner |
| 14 | Backup location | `<backup-location-path-or-service>` | Owner, Developer |

## Rules

- Secrets live in **exactly two places**: the service that issued them (Hostinger, Razorpay, GitHub) and the server `.env` / a password manager.
- **Never** paste secrets into chat, email, screenshots, documents, or GitHub.
- If a secret is ever shared by accident, **rotate it immediately** (generate a new one, update `.env`, clear the app cache) and tell the developer.
- Admin passwords: prefer **Temporary admin access** (see `admin-guide.md`) over sharing the main login.

## Who to contact

| Role | Name | Contact |
|---|---|---|
| Store owner (account holder) | _(your name)_ | _(your phone/email)_ |
| Developer (Codex) | _(name)_ | _(phone/email)_ |

---
*Related: [README.md](README.md) · [razorpay-guide.md](razorpay-guide.md) · [database-guide.md](database-guide.md)*
