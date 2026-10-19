<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MEJF\LaPosteShipping\Listeners\CartQuoteGuard;
use MEJF\LaPosteShipping\Listeners\OrderQuoteGuard;

final class LaPosteShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/carriers.php', 'carriers');
        $this->mergeConfigFrom(__DIR__.'/../Config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'laposte');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'laposte');

        Route::middleware(['web', 'admin'])
            ->prefix(config('app.admin_url', 'admin').'/laposte-shipping')
            ->name('admin.laposte.')
            ->group(__DIR__.'/../Routes/admin.php');

        // Bagisto 2.4.13 events, verified against its Checkout Cart / OrderRepository.
        Event::listen('checkout.cart.collect.totals.before', [CartQuoteGuard::class, 'refresh']);
        Event::listen('checkout.order.save.before', [OrderQuoteGuard::class, 'verify']);
    }
}
