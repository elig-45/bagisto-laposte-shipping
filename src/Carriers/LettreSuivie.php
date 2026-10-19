<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Carriers;

final class LettreSuivie extends ConfiguredCarrier
{
    protected $code = 'laposte_lettre_suivie';
    protected $method = 'laposte_lettre_suivie_laposte_lettre_suivie';

    protected function serviceCode(): string
    {
        return 'lettre_verte_suivie';
    }
}
