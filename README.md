# MONRICX

Laravel/MySQL ecommerce storefront and administration application for deployment on Hostinger Business Web Hosting.

## Runtime

- PHP 8.3 or 8.4
- Laravel 12
- MySQL
- Blade, Alpine.js, Tailwind CSS, and Vite
- Razorpay PHP SDK
- Database sessions and queues; file cache; no Redis or persistent Node process required

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Add local MySQL credentials to .env
php artisan migrate
npm install
npm run build
php artisan serve
```

The database seeder intentionally creates no users, products, or storefront content. Create the first super administrator with a deployment-only command in the admin implementation phase.

## Verification

```bash
composer test
npm run build
php artisan about
```

See [docs/HOSTINGER_DEPLOYMENT.md](docs/HOSTINGER_DEPLOYMENT.md) for the shared-hosting deployment procedure.
