<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Carriers;

final class LettreVerte extends ConfiguredCarrier
{
    protected $code = 'laposte_lettre_verte';
    protected $method = 'laposte_lettre_verte_laposte_lettre_verte';

    protected function serviceCode(): string
    {
        return 'lettre_verte';
    }
}
