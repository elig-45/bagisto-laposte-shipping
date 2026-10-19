<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

/** Small pure service used by both the DB resolver and isolated unit tests. */
final class RateSelection
{
    /** @param list<array{max_weight_g:int,classic_cents:int,online_cents:int,enabled:bool}> $tiers */
    public function select(array $tiers, int $weightMg, string $mode): ?int
    {
        if ($weightMg <= 0 || $weightMg > 2_000_000 || ! in_array($mode, ['classic', 'online'], true)) {
            return null;
        }
        usort($tiers, static fn (array $a, array $b): int => $a['max_weight_g'] <=> $b['max_weight_g']);
        foreach ($tiers as $tier) {
            if ($weightMg <= $tier['max_weight_g'] * 1000) {
                if (! $tier['enabled']) {
                    return null; // Disabled bucket does not silently fall through to a more expensive one.
                }
                return $tier[$mode.'_cents'];
            }
        }
        return null;
    }
}
