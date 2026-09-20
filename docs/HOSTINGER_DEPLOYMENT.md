# Hostinger Business Web Hosting deployment

## 1. Hosting configuration

1. In hPanel, set the hosting plan and website to PHP 8.4.
2. Create a MySQL database in **Websites → Dashboard → Databases → Management**.
3. Record the full prefixed database name and username. Hostinger's database host is `localhost`.
4. Enable SSH and Git deployment.

## 2. Environment

Create `.env` on the server from `.env.example`. Never commit it.

```dotenv
APP_NAME=MONRICX
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-staging-domain.example
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u000000000_monricx
DB_USERNAME=u000000000_monricx_user
DB_PASSWORD=replace-with-hpanel-password
```

Add Razorpay Test Mode values only after the database migration succeeds. Keep all secrets in `.env`.

```dotenv
RAZORPAY_KEY_ID=rzp_test_replace_me
RAZORPAY_KEY_SECRET=replace_me
RAZORPAY_WEBHOOK_SECRET=use_a_separate_random_webhook_secret
RAZORPAY_CURRENCY=INR
STOCK_RESERVATION_MINUTES=20
```

In the Razorpay Test Mode dashboard, enable automatic capture and configure the webhook endpoint as:

```text
https://your-staging-domain.example/razorpay/webhook
```

Subscribe to `payment.authorized`, `payment.captured`, `payment.failed`, and `order.paid`. The webhook secret is created for the webhook and is different from the API key secret.

For online payment, shipping is ₹89 when the merchandise subtotal is ₹399 or less and free when it is above ₹399. Razorpay receives the merchandise subtotal plus any shipping charge. Cash on Delivery has no minimum order value: Razorpay collects a ₹89 COD charge on every COD order, and the merchandise subtotal remains due on delivery.

## 3. Install and optimize

From the deployed project root over SSH:

```bash
composer2 install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
php artisan optimize
```

Vite assets are built locally and committed under `public/build`; Node.js does not run on the shared server.

This Hostinger staging website currently serves static files from a separate `public_html/` directory. After each asset build is uploaded, copy the build into that document root as well:

```bash
cp -a public/build/. public_html/build/
```

Run this only inside the staging domain directory. Do not copy the staging application, database, `.env`, or Razorpay Test configuration into the live `monricx.com` directory.

The repository-level `.htaccess` denies direct access to application and dependency directories, then routes requests to Laravel's `public` directory. If hPanel allows the domain document root to target `public/` directly, prefer that configuration and use Laravel's standard `public/.htaccess`.

## 4. Permissions

Laravel must be able to write to `storage/` and `bootstrap/cache/`. If required:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

## 5. Scheduler and queues

Create one hPanel cron job that runs every minute. Replace the username and domain path:

```bash
/opt/alt/php84/usr/bin/php /home/u000000000/domains/example.com/artisan schedule:run
```

The scheduler drains the database queue using a short-lived worker, avoiding a persistent daemon on shared hosting.

## 6. Release checks

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan config:show database
```

Confirm `APP_DEBUG=false`, HTTPS is forced in hPanel, `/up` returns HTTP 200, and `/admin/login` loads before enabling Razorpay Live Mode.
