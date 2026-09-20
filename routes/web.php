<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\TemporaryAccessController as AdminTemporaryAccessController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\PaymentVerificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', StorefrontController::class)->name('home');
Route::get('/shop', [CatalogController::class, 'index'])->name('shop');
Route::view('/privacy-policy', 'storefront.privacy-policy')->name('privacy-policy');
Route::view('/refund-policy', 'storefront.refund-policy')->name('refund-policy');
Route::view('/faq', 'storefront.faq')->name('faq');
Route::view('/contact', 'storefront.contact')->name('contact');
Route::view('/about', 'storefront.about')->name('about');
Route::get('/{category}', [CatalogController::class, 'category'])
    ->whereIn('category', ['rings', 'earings', 'necklace-and-pendants', 'bracelets'])
    ->name('category');

Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('checkout.store');
Route::post('/checkout/payment/verify', PaymentVerificationController::class)
    ->middleware('throttle:20,1')
    ->name('checkout.payment.verify');
Route::get('/checkout/order/{orderPublicId}', OrderConfirmationController::class)
    ->name('order.confirmation');
Route::post('/razorpay/webhook', RazorpayWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('razorpay.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('admin.login.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

    Route::middleware('admin_permission:products')->group(function () {
        Route::resource('products', AdminProductController::class)->except('show');
    });

    Route::middleware('admin_permission:orders')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/fulfilment', [AdminOrderController::class, 'updateFulfilment'])
            ->name('orders.fulfilment.update');
    });

    Route::get('/analytics', AdminAnalyticsController::class)
        ->middleware('admin_permission:analytics')
        ->name('analytics');

    Route::middleware('super_admin')->group(function () {
        Route::get('/account', [AdminAccountController::class, 'edit'])->name('account.edit');
        Route::patch('/account/password', [AdminAccountController::class, 'update'])
            ->middleware('throttle:5,1')
            ->name('account.password.update');
        Route::get('/temporary-access', [AdminTemporaryAccessController::class, 'index'])
            ->name('temporary-access.index');
        Route::post('/temporary-access', [AdminTemporaryAccessController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('temporary-access.store');
        Route::delete('/temporary-access/{temporaryAdminAccess}', [AdminTemporaryAccessController::class, 'destroy'])
            ->name('temporary-access.destroy');
    });
});

Route::get('/{productSlug}', ProductController::class)
    ->where('productSlug', '[-A-Za-z0-9_]+')
    ->name('product.show');
