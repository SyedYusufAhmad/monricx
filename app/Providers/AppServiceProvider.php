<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\CartService;
use App\Services\RazorpayGateway;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, RazorpayGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.storefront', function ($view): void {
            $view->with('cartSummary', app(CartService::class)->summary(request()));
        });
    }
}
