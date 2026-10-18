# Change Log — Major Milestones

A high-level history of what was built and delivered for the MONRICX store. Technical detail lives in the GitHub commit history; this is the plain-language record.

## Storefront & platform

- **Staging Laravel application** — the full store (homepage, catalog, product pages, cart, checkout) built and running on a staging domain for safe testing.
- **PHP 8.4 setup** — server configured with PHP 8.4 and all required extensions; memory and upload limits tuned for the catalog.
- **MySQL database** — production database created and configured on Hostinger, separate from staging.
- **Admin panel** — private administration for products, orders, discount codes, analytics, and temporary admin access.

## Catalog & checkout

- **Product catalog** — full catalog loaded (48 products) with categories, images, pricing, and stock tracking.
- **Checkout and COD** — online checkout plus Cash on Delivery: ₹89 shipping on online orders at or below ₹399 (free above ₹399); COD collects an ₹89 fee online per order with the balance paid in cash on delivery.

## Payments

- **Razorpay test setup** — test-mode payments verified on staging before going live.
- **Razorpay live setup** — live account activated; live keys and webhook secret installed on production; a real ₹110 test payment succeeded.

## Going live

- **Production deployment** — the Laravel application deployed to `https://monricx.com` with SSL active on both the apex and `www` domains.
- **Apex DNS routing** — investigated a period where `www.monricx.com` served the new store while the apex domain still showed the old site builder; root cause identified as a stale CDN backend mapping and a fix proposed.

## Product management upgrades

- **SKU support** — optional SKU number field added to products (admin only; blank for existing products is normal); unique per product.
- **SKU search & listing** — admin product search now matches title, subtitle, SKU, and URL slug; SKU shown as a column in the product list.
- **Order SKU display** — each product line on the admin order page shows its SKU for accurate dispatch picking.

## Refunds

- **Refund webhooks** — Razorpay `refund.created` / `refund.processed` / `refund.failed` events now update the store automatically; refund status, refund ID, amount, and completion time are recorded per payment.
- **Refund status in admin** — orders list shows a Refund column; the order detail page shows the full refund status (Not refunded → Requested → Processing → Refunded / Failed).
- **Admin refund refresh** — a "Refresh from Razorpay" button on the order page pulls the latest refund status on demand (read-only; it never creates a refund).

## Who to contact

| Role | Name | Contact |
|---|---|---|
| Store owner | _(your name)_ | _(your phone/email)_ |
| Developer (Codex) | _(name)_ | _(phone/email)_ |

---
*Full technical history: GitHub commit log at `https://github.com/SyedYusufAhmad/monricx/commits/main`*
