<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Listeners;

use MEJF\LaPosteShipping\Services\QuoteCalculator;
use RuntimeException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Tax\Facades\Tax;

final class OrderQuoteGuard
{
    /** Bagisto dispatches checkout.order.save.before with [$orderData], unpacked by Laravel. */
    public function verify(array $data): void
    {
        $cart = Cart::getCart();
        if (! $cart || $cart->shipping_method !== QuoteCalculator::METHOD) {
            return;
        }
        $fresh = app(QuoteCalculator::class)->quote($cart);
        $order = $data;
        $expected = Tax::isInclusiveTaxShippingPrices()
            ? ($order['base_shipping_amount_incl_tax'] ?? null)
            : ($order['base_shipping_amount'] ?? null);
        if (! $fresh || ! is_numeric($expected)
            || abs(((float) $expected) - $fresh['cents'] / 100) > 0.005
            || ($order['shipping_method'] ?? '') !== QuoteCalculator::METHOD) {
            throw new RuntimeException(trans('laposte::app.quote_changed'));
        }
    }
}
