<?php

use Elig45\LaPosteShipping\Services\ServiceCatalog;

$carriers = [];

foreach (ServiceCatalog::all() as $serviceCode => $service) {
    $carriers[$service['carrier_code']] = [
        'code' => $service['carrier_code'],
        'title' => $service['title'],
        'description' => $service['description'],
        'active' => false,
        'class' => $service['class'],
    ];
}

return $carriers;
