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

Some Hostinger PHP CLI installations disable `proc_open`. In that environment,
do **not** schedule `artisan schedule:run`: Laravel uses `proc_open` to launch
scheduled commands and the cron will fail.

Create these two hPanel cron jobs, both running every minute. Replace the
username and domain path:

```bash
/opt/alt/php84/usr/bin/php /home/u000000000/domains/example.com/artisan queue:work database --stop-when-empty --tries=3 --max-time=50
```

```bash
/opt/alt/php84/usr/bin/php /home/u000000000/domains/example.com/artisan commerce:release-expired-reservations
```

Only disable an existing `schedule:run` cron after both direct jobs are saved.
An empty queue worker output is normal; the reservation command reports the
number of released reservations.

## 6. Safe GitHub release procedure

The staging directory contains Hostinger-only files that GitHub does not track:
`.env`, `vendor/`, `storage/`, `public_html/`, and `backups/`. Never deploy with
`rsync --delete` from a GitHub archive, because it can remove those files and
take the site offline.

From the staging Laravel project directory, release a reviewed commit with this
pattern. Substitute only the reviewed full commit SHA.

```bash
APP_DIR="$PWD"
PHP_BIN="/opt/alt/php84/usr/bin/php"
RELEASE_SHA="paste-reviewed-full-commit-sha"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_FILE="$APP_DIR/backups/staging-pre-${RELEASE_SHA:0:7}-${STAMP}.tar.gz"
TMP_RELEASE="$(mktemp -d)"

mkdir -p "$APP_DIR/backups"
tar --exclude='./backups' --exclude='./.git' -czf "$BACKUP_FILE" -C "$APP_DIR" .

curl -fL "https://github.com/SyedYusufAhmad/monricx/archive/${RELEASE_SHA}.tar.gz" -o "$TMP_RELEASE/release.tar.gz"
tar -xzf "$TMP_RELEASE/release.tar.gz" -C "$TMP_RELEASE"
RELEASE_DIR="$(find "$TMP_RELEASE" -mindepth 1 -maxdepth 1 -type d -name 'monricx-*' -print -quit)"
test -f "$RELEASE_DIR/artisan"

rsync -a \
  --exclude='.env' \
  --exclude='storage/***' \
  --exclude='vendor/***' \
  --exclude='public_html/***' \
  --exclude='backups/***' \
  --exclude='.git/***' \
  "$RELEASE_DIR/" "$APP_DIR/"

mkdir -p "$APP_DIR/public_html/build"
rsync -a "$APP_DIR/public/build/" "$APP_DIR/public_html/build/"

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize
rm -rf "$TMP_RELEASE"
```

Do not run `migrate --force` by default. Run it only when the reviewed release
contains intended migrations and a fresh database backup exists.

## 7. Release checks

```bash
curl -sS -o /dev/null -w "Homepage: %{http_code}\n" https://your-staging-domain.example/
curl -sS -o /dev/null -w "Health check: %{http_code}\n" https://your-staging-domain.example/up
curl -sS -o /dev/null -w "Admin login: %{http_code}\n" https://your-staging-domain.example/admin/login
```

`artisan about` may fail on this Hostinger CLI because of the disabled
`proc_open` function; this does not by itself indicate a web application fault.
Confirm `APP_DEBUG=false`, HTTPS is forced in hPanel, the three checks return
HTTP 200, and `/admin/login` loads before enabling Razorpay Live Mode.
