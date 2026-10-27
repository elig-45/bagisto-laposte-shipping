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
    public function quote(object $cart, string $serviceCode = 'lettre_verte_suivie'): ?array
    {
        $service = ServiceCatalog::get($serviceCode);
        if (! $service) {
            return null;
        }

        $configKey = 'sales.carriers.'.$service['carrier_code'].'.';
        if (! (bool) core()->getConfigData($configKey.'active')
            || ! (bool) core()->getConfigData($configKey.'packing_confirmed')
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
            'package_length_mm','package_width_mm','package_height_mm',
        ];
        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = core()->getConfigData($configKey.$field);
        }
        $settings['packing_type'] = $service['packing_type'];
        $settings['max_weight_g'] = $service['max_weight_g'];
        $settings['package_limits'] = $service['package_limits'] ?? null;
        if (($settings['allowed_destinations'] ?? null) !== 'metropolitan_france') {
            return null;
        }
        $physical = $this->cartAdapter->analyse($cart, $settings);
        if (! $physical) {
            return null;
        }
        $rate = $this->rates->resolveForService($serviceCode, $physical['weight_mg'], (string) ($settings['price_mode'] ?? 'classic'));
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
