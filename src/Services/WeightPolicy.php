<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Services;

use InvalidArgumentException;

final class WeightPolicy
{
    /**
     * @param list<array{weight:string|int|float|null,quantity:int,physical:bool}> $items
     * @return int|null Total weight in whole milligrams; null means ineligible.
     */
    public function totalMg(array $items, string $unit, array $settings): ?int
    {
        $total = 0;
        $seen = false;
        foreach ($items as $item) {
            if (! $item['physical']) {
                continue;
            }
            $seen = true;
            $qty = $item['quantity'];
            if ($qty < 1 || $qty > 100000) {
                return null;
            }
            $raw = $item['weight'];
            try {
                if ($raw === null || trim((string) $raw) === '' || trim((string) $raw) === '0'
                    || preg_match('/^0+(?:\.0+)?$/D', trim((string) $raw)) === 1) {
                    if (($settings['missing_weight_policy'] ?? 'disable') !== 'substitute') {
                        return null;
                    }
                    $each = Decimal::gramsMg((string) ($settings['substitute_weight_g'] ?? '0'));
                } else {
                    $each = Decimal::weightMg($raw, $unit);
                }
            } catch (InvalidArgumentException) {
                return null; // malformed/negative values NEVER qualify for a substitute
            }
            if ($each < 1) {
                return null;
            }
            $total += $each * $qty;
            if ($total > 2_000_000) {
                return null;
            }
        }
        if (! $seen) {
            return null;
        }
        try {
            $total += Decimal::gramsMg((string) ($settings['packaging_weight_g'] ?? '0'));
        } catch (InvalidArgumentException) {
            return null;
        }
        return $total > 0 && $total <= 2_000_000 ? $total : null;
    }
}
