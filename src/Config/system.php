<?php

use Elig45\LaPosteShipping\Services\ServiceCatalog;

$text = static fn (string $name, string $label, string $default, string $rule, bool $locale = false): array => [
    'name' => $name,
    'title' => 'laposte::app.'.$label,
    'type' => 'text',
    'default' => $default,
    'validation' => $rule,
    'channel_based' => false,
    'locale_based' => $locale,
];
$select = static fn (string $name, string $label, array $options, string $default): array => [
    'name' => $name,
    'title' => 'laposte::app.'.$label,
    'type' => 'select',
    'default' => $default,
    'options' => array_map(
        static fn ($key, $translation): array => ['value' => $key, 'title' => 'laposte::app.'.$translation],
        array_keys($options), array_values($options)
    ),
    'channel_based' => false,
    'locale_based' => false,
];

$configuration = [];

foreach (ServiceCatalog::all() as $serviceCode => $service) {
    $fields = [
        ['name' => 'active', 'title' => 'laposte::app.active', 'type' => 'boolean', 'default' => false, 'channel_based' => false],
        ['name' => 'packing_confirmed', 'title' => 'laposte::app.packing_confirmed', 'type' => 'boolean', 'default' => false, 'channel_based' => false],
        $text('title', 'title', $service['title'], 'required|max:160', true),
        $text('description', 'description', $service['description'], 'max:500', true),
    ];

    if (count($service['price_modes']) > 1) {
        $fields[] = $select('price_mode', 'price_mode', $service['price_modes'], 'classic');
    }

    $fields = array_merge($fields, [
        $text('packaging_weight_g', 'packaging_weight_g', '0', 'required|regex:/^\\d{1,5}(\\.\\d{1,3})?$/'),
        $text('packaging_cost_eur', 'packaging_cost_eur', '0', 'required|regex:/^\\d{1,5}(\\.\\d{1,2})?$/'),
        $select('surcharge_type', 'surcharge_type', ['none' => 'none', 'fixed' => 'fixed', 'percent' => 'percent'], 'none'),
        $text('surcharge_value', 'surcharge_value', '0', 'required|regex:/^\\d{1,5}(\\.\\d{1,2})?$/'),
        $text('free_threshold_eur', 'free_threshold_eur', '0', 'required|regex:/^\\d{1,7}(\\.\\d{1,2})?$/'),
        $select('missing_weight_policy', 'missing_weight_policy', ['disable' => 'disable', 'substitute' => 'substitute'], 'disable'),
        $text('substitute_weight_g', 'substitute_weight_g', '0', 'required|regex:/^\\d{1,5}(\\.\\d{1,3})?$/'),
        $select('allowed_destinations', 'allowed_destinations', ['metropolitan_france' => 'metropolitan_france'], 'metropolitan_france'),
    ]);

    if ($service['packing_type'] === 'letter') {
        $fields = array_merge($fields, [
            $text('envelope_length_mm', 'envelope_length_mm', '240', 'required|integer|between:140,600'),
            $text('envelope_width_mm', 'envelope_width_mm', '160', 'required|integer|between:90,600'),
            $text('envelope_thickness_mm', 'envelope_thickness_mm', '1', 'required|integer|between:0,29'),
        ]);
    } else {
        $limits = $service['package_limits'];
        $fields = array_merge($fields, [
            $text('package_length_mm', 'package_length_mm', (string) $limits['min_length_mm'], 'required|integer|between:'.$limits['min_length_mm'].','.$limits['max_length_mm']),
            $text('package_width_mm', 'package_width_mm', (string) $limits['min_width_mm'], 'required|integer|between:'.$limits['min_width_mm'].','.$limits['max_length_mm']),
            $text('package_height_mm', 'package_height_mm', (string) $limits['min_height_mm'], 'required|integer|between:'.$limits['min_height_mm'].','.$limits['max_length_mm']),
        ]);
    }

    $configuration[] = [
        'key' => 'sales.carriers.'.$service['carrier_code'],
        'name' => 'laposte::app.service_'.$serviceCode,
        'info' => 'laposte::app.info_'.$serviceCode,
        'sort' => 15,
        'fields' => $fields,
    ];
}

return $configuration;
