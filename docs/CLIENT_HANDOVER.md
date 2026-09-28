# MONRICX client handover

## Current delivery

- **Staging storefront:** `https://lemonchiffon-kangaroo-819794.hostingersite.com/`
- **Staging administration:** `https://lemonchiffon-kangaroo-819794.hostingersite.com/admin/login`
- **Verified release:** `a97604e` — coupon shipping and redemption rules
- **Production:** `https://monricx.com/` remains separate and has not been changed by this staging work.

No passwords, Razorpay keys, database credentials, or GitHub tokens are stored
in this document or the repository.

## Included functionality

- Responsive MONRICX storefront, catalog, category pages, product pages, cart
  drawer, and checkout.
- Product administration for title, subtitle, price, sale price, stock,
  description, images, and publication status.
- Order administration, sales/view analytics, temporary admin access, and
  discount-code administration.
- Razorpay Test Mode online payments and COD booking-fee flow.
- India delivery-state selection and shipping rules:
  - Online orders above ₹399 have free shipping; ₹399 or less adds ₹89.
  - COD always collects ₹89 online through Razorpay; the discounted product
    amount remains payable on delivery.
- Coupon rules:
  - Free-shipping eligibility uses the pre-discount merchandise subtotal.
  - Maximum discount caps apply only to percentage codes.
  - Percentage codes above 100% are rejected.
  - Usage is counted only after the relevant Razorpay payment is captured.

## Operating checklist

1. Manage products, stock, and discount codes from the staging admin area.
2. Use Razorpay Test Mode while staging. Do not reuse Test keys, webhook
   secrets, or URLs for production.
3. Before each staging release, record the reviewed Git commit and take a
   current staging backup. Follow `docs/HOSTINGER_DEPLOYMENT.md`.
4. Check the homepage, `/up`, and `/admin/login` after every release.
5. Keep the two direct Hostinger cron jobs enabled; do not restore the
   `schedule:run` cron on this hosting environment.

## Acceptance evidence

- Automated release verification: 51 tests, 331 assertions passed.
- Staging homepage, health endpoint, and admin login returned HTTP 200 after
  the verified release.
- Storefront, product, category, policy, cart-drawer, and checkout rendering
  were reviewed on staging without browser-console errors.

## Before a production launch

Production launch is a separate, explicit approval step. First take fresh
production database and file backups, set production-only Razorpay Live keys
and webhook credentials in production `.env`, use the safe release process,
run checkout/payment verification, and retain the previous release for
rollback.
