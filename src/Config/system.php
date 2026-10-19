<?php

$number = static fn (string $name, string $label, string $default, string $rule): array => [
    'name' => $name,
    'title' => 'laposte::app.'.$label,
    'type' => 'text',
    'default' => $default,
    'validation' => $rule,
    'channel_based' => false,
    'locale_based' => false,
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

return [[
    'key' => 'sales.carriers.laposte_lettre_suivie',
    'name' => 'laposte::app.method',
    'info' => 'laposte::app.info',
    'sort' => 15,
    'fields' => [
        ['name' => 'active', 'title' => 'laposte::app.active', 'type' => 'boolean', 'default' => false, 'channel_based' => false],
        ['name' => 'packing_confirmed', 'title' => 'laposte::app.packing_confirmed', 'type' => 'boolean', 'default' => false, 'channel_based' => false],
        ['name' => 'title', 'title' => 'laposte::app.title', 'type' => 'text', 'default' => 'La Poste – Lettre verte suivie', 'validation' => 'required|max:160', 'channel_based' => false, 'locale_based' => true],
        ['name' => 'description', 'title' => 'laposte::app.description', 'type' => 'textarea', 'default' => 'Livraison suivie à domicile · Environ 3 jours ouvrables après expédition', 'validation' => 'max:500', 'channel_based' => false, 'locale_based' => true],
        $select('price_mode', 'price_mode', ['classic' => 'classic', 'online' => 'online'], 'classic'),
        $number('packaging_weight_g', 'packaging_weight_g', '0', 'required|regex:/^\d{1,4}(\.\d{1,3})?$/'),
        $number('packaging_cost_eur', 'packaging_cost_eur', '0', 'required|regex:/^\d{1,5}(\.\d{1,2})?$/'),
        $select('surcharge_type', 'surcharge_type', ['none' => 'none', 'fixed' => 'fixed', 'percent' => 'percent'], 'none'),
        $number('surcharge_value', 'surcharge_value', '0', 'required|regex:/^\d{1,5}(\.\d{1,2})?$/'),
        $number('free_threshold_eur', 'free_threshold_eur', '0', 'required|regex:/^\d{1,7}(\.\d{1,2})?$/'),
        $select('missing_weight_policy', 'missing_weight_policy', ['disable' => 'disable', 'substitute' => 'substitute'], 'disable'),
        $number('substitute_weight_g', 'substitute_weight_g', '0', 'required|regex:/^\d{1,4}(\.\d{1,3})?$/'),
        $number('envelope_length_mm', 'envelope_length_mm', '240', 'required|integer|between:140,600'),
        $number('envelope_width_mm', 'envelope_width_mm', '160', 'required|integer|between:90,600'),
        $number('envelope_thickness_mm', 'envelope_thickness_mm', '1', 'required|integer|between:0,29'),
        $select('allowed_destinations', 'allowed_destinations', ['metropolitan_france' => 'metropolitan_france'], 'metropolitan_france'),
    ],
]];
