<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

final class QuoteCalculator
{
    public const CODE = 'laposte_lettre_suivie';
    public const METHOD = 'laposte_lettre_suivie_laposte_lettre_suivie';

    public function __construct(
        private readonly MetropolitanFrance $destination,
        private readonly CartAdapter $cartAdapter,
        private readonly RateResolver $rates,
        private readonly PriceAdjustments $adjustments
    ) {}

    /** @return array{cents:int,weight_mg:int,rate_card_id:int}|null */
    public function quote(object $cart): ?array
    {
        if (! (bool) core()->getConfigData('sales.carriers.'.self::CODE.'.active')
            || ! (bool) core()->getConfigData('sales.carriers.'.self::CODE.'.packing_confirmed')
            || core()->getBaseCurrencyCode() !== 'EUR'
            || ! $cart->shipping_address
            || ! $cart->haveStockableItems()) {
            return null;
        }

        $address = $cart->shipping_address;
        if (! $this->destination->accepts($address->country, $address->postcode)) {
            return null;
        }

        $fields = [
            'price_mode','allowed_destinations','packaging_weight_g','packaging_cost_eur',
            'surcharge_type','surcharge_value','free_threshold_eur','missing_weight_policy',
            'substitute_weight_g','envelope_length_mm','envelope_width_mm','envelope_thickness_mm',
        ];
        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = core()->getConfigData('sales.carriers.'.self::CODE.'.'.$field);
        }
        if (($settings['allowed_destinations'] ?? null) !== 'metropolitan_france') {
            return null;
        }
        $physical = $this->cartAdapter->analyse($cart, $settings);
        if (! $physical) {
            return null;
        }
        $rate = $this->rates->resolve($physical['weight_mg'], (string) ($settings['price_mode'] ?? 'classic'));
        if (! $rate) {
            return null;
        }
        $final = $this->adjustments->apply($rate['cents'], $settings, $physical['subtotal_cents']);
        if ($final === null) {
            return null;
        }
        return ['cents' => $final, 'weight_mg' => $physical['weight_mg'], 'rate_card_id' => $rate['card_id']];
    }
}
