<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Carriers;

use Elig45\LaPosteShipping\Services\QuoteCalculator;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;

abstract class ConfiguredCarrier extends AbstractShipping
{
    abstract protected function serviceCode(): string;

    public function calculate(): CartShippingRate|false
    {
        $cart = Cart::getCart();
        if (! $cart) {
            return false;
        }

        $quote = app(QuoteCalculator::class)->quote($cart, $this->serviceCode());
        if ($quote === null) {
            return false;
        }

        $rate = new CartShippingRate;
        $rate->carrier = $this->code;
        $rate->method = $this->method;
        $rate->carrier_title = $rate->method_title = $this->getGlobal('title')
            ?: trans('laposte::app.service_'.$this->serviceCode());
        $description = $this->getGlobal('description')
            ?: trans('laposte::app.description_'.$this->serviceCode());
        $rate->method_description = ($quote['cents'] === 0 ? trans('laposte::app.free').' · ' : '').$description;
        $rate->base_price = $quote['cents'] / 100;
        $rate->price = round((float) core()->convertPrice($rate->base_price), 4);
        $rate->base_price_incl_tax = $rate->base_price;
        $rate->price_incl_tax = $rate->price;

        return $rate;
    }

    private function getGlobal(string $field): ?string
    {
        return core()->getConfigData('sales.carriers.'.$this->code.'.'.$field);
    }
}
