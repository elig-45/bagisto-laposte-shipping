<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Listeners;

use Illuminate\Http\Exceptions\HttpResponseException;
use Elig45\LaPosteShipping\Services\ServiceCatalog;
use Webkul\Tax\Facades\Tax;

final class CartQuoteGuard
{
    public function refresh(object $cart): void
    {
        $service = ServiceCatalog::fromMethod($cart->shipping_method);
        if (! $service) {
            return;
        }
        $method = $service['method'];
        $existing = $cart->shipping_rates()->where('method', $method)->first();
        $fresh = app($service['class'])->calculate();
        // Exclude unselected or now unavailable method, without suppressing other carriers.
        if (! $existing || ! $fresh) {
            $cart->shipping_rates()->where('method', $method)->delete();
            $cart->unsetRelation('shipping_rates');
            return;
        }
        $previousGrossOrNet = Tax::isInclusiveTaxShippingPrices()
            ? (float) $existing->base_price_incl_tax
            : (float) $existing->base_price;
        $changed = abs($previousGrossOrNet - (float) $fresh->base_price) > 0.005;
        // Do not silently increase the displayed price at order placement.
        if ($changed && request()->routeIs('shop.checkout.onepage.orders.store')) {
            throw new HttpResponseException(response()->json([
                'message' => trans('laposte::app.quote_changed'),
            ], 409));
        }
        $existing->fill([
            'carrier_title' => $fresh->carrier_title,
            'method_title' => $fresh->method_title,
            'method_description' => $fresh->method_description,
            'base_price' => $fresh->base_price,
            'price' => $fresh->price,
            'base_price_incl_tax' => $fresh->base_price,
            'price_incl_tax' => $fresh->price,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'base_tax_amount' => 0,
        ]);
        $existing->save();
        $cart->unsetRelation('shipping_rates');
    }
}
