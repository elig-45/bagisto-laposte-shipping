<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Services;

/** France métropolitaine (incl. Corsica); FR alone also covers overseas territories. */
final class MetropolitanFrance
{
    public function accepts(?string $country, ?string $postcode): bool
    {
        return strtoupper(trim((string) $country)) === 'FR'
            && preg_match('/^(?:0[1-9]|[1-8][0-9]|9[0-5])[0-9]{3}$/D', trim((string) $postcode)) === 1;
    }
}
