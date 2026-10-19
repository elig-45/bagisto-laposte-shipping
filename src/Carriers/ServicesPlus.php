<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Carriers;

final class ServicesPlus extends ConfiguredCarrier
{
    protected $code = 'laposte_services_plus';
    protected $method = 'laposte_services_plus_laposte_services_plus';

    protected function serviceCode(): string
    {
        return 'services_plus';
    }
}
