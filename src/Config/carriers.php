<?php

return [
    'laposte_lettre_suivie' => [
        'code' => 'laposte_lettre_suivie',
        'title' => 'La Poste – Lettre verte suivie',
        'description' => 'Livraison suivie à domicile · Environ 3 jours ouvrables après expédition',
        'active' => false,
        'class' => \MEJF\LaPosteShipping\Carriers\LettreSuivie::class,
    ],
];
