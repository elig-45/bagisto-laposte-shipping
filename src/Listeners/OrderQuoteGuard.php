<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Listeners;

use Elig45\LaPosteShipping\Services\ServiceCatalog;
use RuntimeException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Tax\Facades\Tax;

final class OrderQuoteGuard
{
    /** Bagisto dispatches checkout.order.save.before with [$orderData], unpacked by Laravel. */
    public function verify(array $data): void
    {
        $cart = Cart::getCart();
        $service = ServiceCatalog::fromMethod($cart?->shipping_method);
        if (! $cart || ! $service) {
            return;
        }
        $fresh = app(\Elig45\LaPosteShipping\Services\QuoteCalculator::class)
            ->quote($cart, $service['service_code']);
        $order = $data;
        $expected = Tax::isInclusiveTaxShippingPrices()
            ? ($order['base_shipping_amount_incl_tax'] ?? null)
            : ($order['base_shipping_amount'] ?? null);
        if (! $fresh || ! is_numeric($expected)
            || abs(((float) $expected) - $fresh['cents'] / 100) > 0.005
            || ($order['shipping_method'] ?? '') !== $service['method']) {
            throw new RuntimeException(trans('laposte::app.quote_changed'));
        }
    }
}
