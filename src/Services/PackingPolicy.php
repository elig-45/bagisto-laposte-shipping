<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

/**
 * A physically achievable conservative packing method, NOT volume estimation.
 * Each individual item is explicitly measured flat in its shipping-ready state;
 * all items are laid directly on top of each other without turning or folding.
 */
final class PackingPolicy
{
    /** @param list<array{length_mm:int,width_mm:int,thickness_mm:int,quantity:int,eligible:bool,packing_rule:string}> $items */
    public function accepts(array $items, array $envelope): bool
    {
        if (($envelope['packing_type'] ?? 'letter') === 'parcel') {
            return $this->acceptsParcel($items, $envelope);
        }

        $l = (int) ($envelope['envelope_length_mm'] ?? 0);
        $w = (int) ($envelope['envelope_width_mm'] ?? 0);
        $t = (int) ($envelope['envelope_thickness_mm'] ?? -1);
        if ($l < 140 || $w < 90 || $l > 600 || $w > 600 || $t < 0 || $t >= 30 || $l + $w + $t > 900) {
            return false;
        }
        if ($items === []) {
            return false;
        }
        foreach ($items as $item) {
            if (! $item['eligible'] || $item['packing_rule'] !== 'flat_stack' || $item['quantity'] < 1) {
                return false;
            }
            // Both orientations allowed, but no folding, bending, rotation-at-an-angle, or side-by-side assumption.
            $long = max($item['length_mm'], $item['width_mm']);
            $short = min($item['length_mm'], $item['width_mm']);
            if ($long < 1 || $short < 1 || $item['thickness_mm'] < 1
                || $long > max($l, $w) || $short > min($l, $w)) {
                return false;
            }
            $t += $item['thickness_mm'] * $item['quantity'];
            if ($t >= 30 || $l + $w + $t > 900) {
                return false;
            }
        }
        return true;
    }

    /** @param list<array{length_mm:int,width_mm:int,thickness_mm:int,quantity:int,eligible:bool,packing_rule:string}> $items */
    private function acceptsParcel(array $items, array $package): bool
    {
        $limits = $package['package_limits'] ?? [
            'min_length_mm' => 300,
            'min_width_mm' => 210,
            'min_height_mm' => 30,
            'max_length_mm' => 1500,
            'max_girth_mm' => 3000,
        ];
        $dimensions = [
            (int) ($package['package_length_mm'] ?? 0),
            (int) ($package['package_width_mm'] ?? 0),
            (int) ($package['package_height_mm'] ?? 0),
        ];
        rsort($dimensions);
        [$length, $width, $height] = $dimensions;

        if ($length < $limits['min_length_mm']
            || $width < $limits['min_width_mm']
            || $height < $limits['min_height_mm']
            || $length > $limits['max_length_mm']
            || $length + 2 * $width + 2 * $height > $limits['max_girth_mm']
            || $items === []) {
            return false;
        }

        $stackThickness = 0;
        foreach ($items as $item) {
            if (! $item['eligible'] || $item['packing_rule'] !== 'flat_stack' || $item['quantity'] < 1) {
                return false;
            }
            $long = max($item['length_mm'], $item['width_mm']);
            $short = min($item['length_mm'], $item['width_mm']);
            if ($long < 1 || $short < 1 || $item['thickness_mm'] < 1
                || $long > $length || $short > $width) {
                return false;
            }
            $stackThickness += $item['thickness_mm'] * $item['quantity'];
            if ($stackThickness > $height) {
                return false;
            }
        }

        return true;
    }
}
