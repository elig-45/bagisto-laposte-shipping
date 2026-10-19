<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Carriers;

final class Chrono13 extends ConfiguredCarrier
{
    protected $code = 'laposte_chrono_13';
    protected $method = 'laposte_chrono_13_laposte_chrono_13';

    protected function serviceCode(): string
    {
        return 'chrono_13';
    }
}
