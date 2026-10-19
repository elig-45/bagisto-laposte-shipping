<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Services;

/** Future official integration seam. V1 does not call or buy labels via any provider. */
interface ShippingApi
{
    public function track(string $trackingNumber): array;
}
