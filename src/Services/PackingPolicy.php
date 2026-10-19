<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Services;

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
}
