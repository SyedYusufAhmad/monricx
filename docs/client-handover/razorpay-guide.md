# Razorpay Guide — Payments, Webhooks & Refunds

## Live versus test mode

Razorpay has two modes. They are completely separate — keys, payments, and settings do not carry over.

| | Test mode | Live mode |
|---|---|---|
| Real money moves? | **No** — safe for practice | **Yes** |
| Where is it used? | Staging site | Production site (`monricx.com`) |
| Keys | Test Key ID / Test Key Secret | Live Key ID / Live Key Secret |

**Rules:**

- Always test payment changes in **test mode on staging** first.
- **Never** put live keys on staging, and never put test keys on production.
- **Never** expose the Key Secret or Webhook Secret — not in chat, email, screenshots, or GitHub. Anyone with the secret can act on your Razorpay account.

## Where Razorpay credentials are stored

The keys live in the server's `.env` file (production) as:

- `RAZORPAY_KEY_ID`
- `RAZORPAY_KEY_SECRET`
- `RAZORPAY_WEBHOOK_SECRET`

Only the developer manages this file over SSH. If keys ever need rotating (for example, if one was accidentally shared), the developer generates new ones in the Razorpay dashboard and updates `.env`, then clears the application cache.

## Webhook

Razorpay notifies the store automatically about payment events by calling a webhook URL.

- **Webhook URL:** `https://monricx.com/razorpay/webhook`
- **Required events** (all must be enabled in the Razorpay dashboard):

```
payment.authorized
payment.captured
payment.failed
order.paid
refund.created
refund.processed
refund.failed
```

The first four keep payment statuses accurate. The three `refund.*` events keep **refund statuses** accurate — without them, the admin panel won't know when a refund completes.

Each incoming webhook is verified by signature. Events that fail verification are rejected and never processed.

## How to issue a refund in Razorpay

Refunds are created **inside the Razorpay dashboard**, not in the store's admin panel:

1. Log in to the Razorpay dashboard (live mode).
2. Go to **Payments**, find the customer's payment.
3. Click **Refund**, enter the amount (full or partial), and confirm.
4. Razorpay processes the refund and sends `refund.created` → `refund.processed` webhook events to the store.
5. The order's **Refund status** in the admin panel updates automatically (to *Refund processing*, then *Refunded*).

## How to refresh refund status in admin

Normally you don't need to do anything — the webhooks update the status on their own. If a status looks out of date:

1. Open the order at `https://monricx.com/admin/orders/{order}`.
2. In the **Refund status** section, click **Refresh from Razorpay**.
3. The page reloads with the latest status from Razorpay.

> This button only **reads** the current status. It never creates, changes, or cancels a refund.

## Refund processing is separate from fulfilment

- **Refund status** = money going back to the customer (from Razorpay).
- **Fulfilment status** = the physical order: packed, shipped, delivered.

They are independent. Refunding an order does not change its fulfilment history, and updating fulfilment never touches money. If a refunded item was already shipped, handle the return/reverse-pickup as a separate operational step.

## Never create a second refund just for testing

- On **production/live mode**, every refund moves real money. Test refunds only in **test mode on staging**.
- Before refunding, double-check you have the right payment: verify the order number, customer name, and amount.
- If a refund fails (*Refund failed* status), don't immediately retry — check the reason in the Razorpay dashboard first (e.g. the original payment method may no longer support refunds). Ask the developer if unsure.

## Before changing production — checklist

- [ ] You are in the correct Razorpay mode (live = production, test = staging)
- [ ] Webhook URL and all seven events are configured
- [ ] No secrets shared in chat, email, screenshots, or GitHub
- [ ] Refund verified against the correct order and amount before confirming

## Who to contact

| Role | Name | Contact |
|---|---|---|
| Store owner | _(your name)_ | _(your phone/email)_ |
| Developer (Codex) | _(name)_ | _(phone/email)_ |
| Razorpay support | Razorpay | Dashboard → Support |

---
*Related: [admin-guide.md](admin-guide.md) (refund status section) · [access-and-secrets.md](access-and-secrets.md)*
