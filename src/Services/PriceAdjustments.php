<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

use InvalidArgumentException;

final class PriceAdjustments
{
    public function apply(int $postalCents, array $settings, int $subtotalCents): ?int
    {
        try {
            $packaging = Decimal::euroCents((string) ($settings['packaging_cost_eur'] ?? '0'));
            $surcharge = (string) ($settings['surcharge_value'] ?? '0');
            $threshold = Decimal::euroCents((string) ($settings['free_threshold_eur'] ?? '0'));
            $type = $settings['surcharge_type'] ?? 'none';
            $cents = $postalCents + $packaging;
            if ($type === 'fixed') {
                $cents += Decimal::euroCents($surcharge);
            } elseif ($type === 'percent') {
                $bps = Decimal::percentBasisPoints($surcharge);
                $cents += intdiv($cents * $bps + 5000, 10000);
            } elseif ($type !== 'none') {
                return null;
            }
            if ($threshold > 0 && $subtotalCents >= $threshold) {
                $cents = 0;
            }
            return $cents;
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
