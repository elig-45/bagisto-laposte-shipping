<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Carriers;

final class ColissimoHome extends ConfiguredCarrier
{
    protected $code = 'laposte_colissimo_domicile';
    protected $method = 'laposte_colissimo_domicile_laposte_colissimo_domicile';

    protected function serviceCode(): string
    {
        return 'colissimo_domicile';
    }
}
