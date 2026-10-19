<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

use Illuminate\Support\Facades\Log;
use Elig45\LaPosteShipping\Models\ProductProfile;

/** Translates native Bagisto 2.4 cart rows into the pure physical packing rules. */
final class CartAdapter
{
    public function __construct(private readonly WeightPolicy $weightPolicy, private readonly PackingPolicy $packingPolicy) {}

    /** @return array{weight_mg:int,subtotal_cents:int}|null */
    public function analyse(object $cart, array $settings): ?array
    {
        // The host Bagisto channel may use kg or lb even though this carrier's settings are global.
        $channel = $cart->channel?->code;
        if (! $channel) {
            return null;
        }
        $unit = (string) core()->getConfigData('general.general.locale_options.weight_unit', $channel);
        $weightItems = [];
        $dimensions = [];
        $ids = [];
        $subtotal = 0.0;

        foreach ($cart->items as $item) {
            $subtotal += max(0.0, (float) $item->base_total - (float) $item->base_discount_amount);
            if (! $item->getTypeInstance()->isStockable()) {
                continue; // Virtual / downloadable items do not enter shipping weight.
            }
            // Bagisto configurable parents already carry selected child weight.
            // Complex pack structures need an explicit future strategy, never a guess.
            if (! in_array($item->type, ['simple', 'configurable'], true)) {
                Log::warning('La Poste: unsupported composite type', ['type' => $item->type]);
                return null;
            }
            $quantity = (int) $item->quantity;
            $additional = (array) ($item->additional ?? []);
            $id = (int) ($item->type === 'configurable'
                ? ($additional['selected_configurable_option'] ?? $item->child?->product_id ?? 0)
                : $item->product_id);
            if ($id < 1 || $quantity < 1) {
                return null;
            }
            // Do not trust an old cart snapshot after product weight is edited.
            $shippingProduct = $item->type === 'configurable'
                ? $item->child?->product
                : $item->product;
            if (! $shippingProduct || (int) $shippingProduct->id !== $id) {
                Log::warning('La Poste: physical product is no longer available for quotation');
                return null;
            }
            $ids[] = $id;
            $weightItems[] = ['weight' => $shippingProduct->weight, 'quantity' => $quantity, 'physical' => true];
            $dimensions[] = ['product_id' => $id, 'quantity' => $quantity];
        }
        if ($dimensions === []) {
            return null;
        }

        $weight = $this->weightPolicy->totalMg($weightItems, $unit, $settings);
        if ($weight === null) {
            Log::warning('La Poste: invalid or missing cart weight; shipping unavailable');
            return null;
        }

        $profiles = ProductProfile::query()->whereIn('product_id', array_unique($ids))
            ->get()->keyBy('product_id');
        $packing = [];
        foreach ($dimensions as $item) {
            $profile = $profiles->get($item['product_id']);
            if (! $profile) {
                return null;
            }
            $packing[] = [
                'length_mm' => $profile->length_mm,
                'width_mm' => $profile->width_mm,
                'thickness_mm' => $profile->thickness_mm,
                'eligible' => $profile->eligible,
                'packing_rule' => $profile->packing_rule,
                'quantity' => $item['quantity'],
            ];
        }
        if (! $this->packingPolicy->accepts($packing, $settings)) {
            return null;
        }
        // Bagisto product financial totals are floats; perform ONE rounding at the money boundary.
        return ['weight_mg' => $weight, 'subtotal_cents' => max(0, (int) round($subtotal * 100))];
    }
}
