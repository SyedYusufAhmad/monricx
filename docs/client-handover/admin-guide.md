# Admin Guide — Managing Your Store

This guide walks through everyday tasks in the admin panel, step by step. The admin panel is at `https://monricx.com/admin/login` (use the staging address when practising).

## Logging in

1. Go to `https://monricx.com/admin/login`.
2. Enter your admin email and password.
3. You will see the dashboard with tabs for **Products**, **Orders**, **Discount codes**, **Analytics**, and more.

> Keep your admin password private. If someone needs temporary access, use **Temporary admin access** (explained below) instead of sharing your login.

## Products

### Adding a product

1. Click **Products** → **Add product**.
2. Fill in the fields (explained below).
3. Click **Save**. The product appears in the store only if its **Status** is set to Published.

### Editing a product

1. Click **Products**.
2. Find the product (use the search box if needed) and click **Edit**.
3. Change what you need, then **Save**.

### Product fields explained

| Field | What it is | Required? |
|---|---|---|
| Title | The product name customers see | Yes |
| Subtitle | A short tagline under the title | No |
| SKU number | Your internal stock-keeping code (see below) | No — leave blank if none |
| Price | The regular price in rupees | Yes |
| Discounted price | The sale price, if the item is on offer | No |
| Stock | How many units you have | Yes |
| Description | The full product description | Yes |
| Images | Product photos (JPEG, PNG, or WebP; up to 5 MB each, up to 8 per upload) | At least one recommended |
| Category | Which category the product belongs to | Yes |
| Status | **Draft** (hidden from customers) or **Published** (visible in the store) | Yes |

### The SKU number field

- **SKU** means "stock keeping unit" — a short code you use to identify products in your own records (for example, `NCKL-GLD-001`).
- It is **optional**. If a product has no SKU, just leave the field blank.
- Each SKU must be **unique** — two products cannot share the same SKU.
- **Existing products with a blank SKU are perfectly fine.** Blank does not mean broken; it simply means no SKU was assigned. The store works normally.
- The SKU is **only visible in the admin panel**. Customers never see it on the website.

### Finding products (search)

The search box on the Products page matches:

- Product **title**
- Product **subtitle**
- **SKU** number
- Product **URL** (the web address slug, e.g. `golden-heart-pendant`)

Type any part of any of these and press Enter.

## Orders

### The Orders tab

Click **Orders** to see every order. Each row shows the order number, customer, payment method, **payment status**, **refund status**, items, total, and date. Click an order to open its detail page.

### Order SKU for dispatch

On the order detail page, each product line shows its **SKU** next to the product name (a dash `—` means no SKU was assigned). Use this when picking and packing items for dispatch so you ship exactly what was ordered.

### Fulfilment status

Fulfilment tracks **shipping progress** — for example, pending, packed, shipped, delivered. Update it as the order moves through dispatch. This is separate from payment: an order can be fully paid but not yet shipped.

### Payment status

Payment status tracks **money** — for example, whether the online payment was captured or the COD fee was received. It updates automatically when Razorpay confirms a payment.

### Refund status

If a customer is refunded, the order's **Refund status** shows what happened. Possible values:

| Status shown | Meaning |
|---|---|
| Not refunded | No refund has been made |
| Refund requested | A refund was asked for |
| Refund processing | Razorpay is processing the refund |
| Refunded | The refund completed |
| Refund failed | The refund did not go through |

Refund status is **separate from fulfilment**. A refunded order still keeps its fulfilment history — refunding does not cancel or delete the order.

### Refresh refund status from Razorpay

Refund statuses normally update **automatically** when Razorpay sends a notification to the store. If a status looks out of date:

1. Open the order in the admin panel.
2. Find the **Refund status** section.
3. Click **Refresh from Razorpay**.

This asks Razorpay for the latest refund information and updates the display. **It does not create a refund** — it only reads the current status. Refunds themselves are always created inside the Razorpay dashboard (see `razorpay-guide.md`).

## Discount codes

1. Click **Discount codes**.
2. Click **Add** to create a code: set the code text (e.g. `DIWALI10`), the discount type (percentage or fixed amount), and any limits.
3. **Usage limits:** percentage discounts cannot exceed 100%, and discounts are capped so a code can never make an order total negative.
4. A code's usage is counted only **after** the customer's payment is successfully captured — abandoned checkouts don't use up the code.

## Temporary admin access

If someone (for example, a helper or accountant) needs short-term access:

1. Go to **Temporary admin access**.
2. Create a time-limited login and choose exactly which areas they may use (products, orders, etc.).
3. The access expires automatically — you don't need to remember to remove it.

Never share your own admin password. Temporary access is the safe alternative.

## Analytics and order views

- **Analytics** shows sales totals, order counts, and product views so you can see what is selling.
- The **Orders** list can be scanned quickly for payment and refund statuses without opening each order.

---
*Related: [razorpay-guide.md](razorpay-guide.md) for refunds and payments · [future-changes.md](future-changes.md) before making any change to the live store.*
