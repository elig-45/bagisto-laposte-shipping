<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

use Elig45\LaPosteShipping\Carriers\Chrono13;
use Elig45\LaPosteShipping\Carriers\ColissimoHome;
use Elig45\LaPosteShipping\Carriers\LettreSuivie;
use Elig45\LaPosteShipping\Carriers\LettreVerte;
use Elig45\LaPosteShipping\Carriers\ServicesPlus;

final class ServiceCatalog
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'lettre_verte_suivie' => [
                'title' => 'La Poste – Lettre verte suivie',
                'description' => 'Livraison suivie à domicile · Environ 3 jours ouvrables après expédition',
                'info' => 'Lettre suivie, France métropolitaine, jusqu’à 2 kg et 3 cm d’épaisseur',
                'carrier_code' => 'laposte_lettre_suivie',
                'method' => 'laposte_lettre_suivie_laposte_lettre_suivie',
                'class' => LettreSuivie::class,
                'packing_type' => 'letter',
                'max_weight_g' => 2000,
                'price_modes' => ['classic' => 'classic', 'online' => 'online'],
                'source_url' => 'https://www.laposte.fr/tarif-lettre-suivie',
                'tiers' => [[20, 202, 199], [100, 360, 347], [250, 574, 568], [500, 791, 771], [1000, 979, 964], [2000, 1164, 1156]],
            ],
            'lettre_verte' => [
                'title' => 'La Poste – Lettre verte',
                'description' => 'Envoi sans suivi à domicile · Environ 3 jours ouvrables après expédition',
                'info' => 'Lettre sans suivi, France métropolitaine, jusqu’à 2 kg et 3 cm d’épaisseur',
                'carrier_code' => 'laposte_lettre_verte',
                'method' => 'laposte_lettre_verte_laposte_lettre_verte',
                'class' => LettreVerte::class,
                'packing_type' => 'letter',
                'max_weight_g' => 2000,
                'price_modes' => ['classic' => 'classic', 'online' => 'online'],
                'source_url' => 'https://www.laposte.fr/tarif-lettre-verte',
                'tiers' => [[20, 152, 149], [100, 310, 297], [250, 524, 518], [500, 741, 721], [1000, 929, 914], [2000, 1114, 1106]],
            ],
            'services_plus' => [
                'title' => 'La Poste – Lettre Services Plus',
                'description' => 'Suivi inclus · Délai indicatif de 2 jours ouvrables',
                'info' => 'Lettre suivie avec Services Plus, France métropolitaine, jusqu’à 2 kg et 3 cm d’épaisseur',
                'carrier_code' => 'laposte_services_plus',
                'method' => 'laposte_services_plus_laposte_services_plus',
                'class' => ServicesPlus::class,
                'packing_type' => 'letter',
                'max_weight_g' => 2000,
                'price_modes' => ['classic' => 'classic', 'online' => 'online'],
                'source_url' => 'https://www.laposte.fr/tarifs-lettre-services-plus',
                'tiers' => [[20, 347, 341], [100, 457, 445], [250, 578, 577], [500, 810, 804], [1000, 1035, 1023], [2000, 1201, 1195]],
            ],
            'colissimo_domicile' => [
                'title' => 'La Poste – Colissimo domicile',
                'description' => 'Livraison à domicile avec suivi · Délai indicatif de 2 jours ouvrables',
                'info' => 'Colissimo domicile en France métropolitaine, jusqu’à 30 kg. Dimensions du colis fermé : minimum 22 × 11 × 1 cm, longueur maximale 100 cm et longueur + 2 × largeur + 2 × hauteur au plus 150 cm.',
                'carrier_code' => 'laposte_colissimo_domicile',
                'method' => 'laposte_colissimo_domicile_laposte_colissimo_domicile',
                'class' => ColissimoHome::class,
                'packing_type' => 'parcel',
                'max_weight_g' => 30000,
                'price_modes' => ['classic' => 'classic'],
                'package_limits' => [
                    'min_length_mm' => 220,
                    'min_width_mm' => 110,
                    'min_height_mm' => 10,
                    'max_length_mm' => 1000,
                    'max_girth_mm' => 1500,
                ],
                'source_url' => 'https://www.laposte.fr/tarif-colissimo',
                'tiers' => [[250, 549, 549], [500, 759, 759], [750, 929, 929], [1000, 959, 959], [2000, 1119, 1119], [5000, 1739, 1739], [10000, 2529, 2529], [15000, 3199, 3199], [30000, 3959, 3959]],
            ],
            'chrono_13' => [
                'title' => 'Chronopost – Chrono 13',
                'description' => 'Livraison express le lendemain avant 13 h en France métropolitaine',
                'info' => 'Colis express à domicile, jusqu’à 30 kg · dimensions extérieures du colis à renseigner',
                'carrier_code' => 'laposte_chrono_13',
                'method' => 'laposte_chrono_13_laposte_chrono_13',
                'class' => Chrono13::class,
                'packing_type' => 'parcel',
                'max_weight_g' => 30000,
                'price_modes' => ['classic' => 'classic'],
                'package_limits' => [
                    'min_length_mm' => 300,
                    'min_width_mm' => 210,
                    'min_height_mm' => 30,
                    'max_length_mm' => 1500,
                    'max_girth_mm' => 3000,
                ],
                'source_url' => 'https://www.laposte.fr/tarif-chronopost',
                'tiers' => [[1000, 2340, 2340], [3000, 2520, 2520], [10000, 2880, 2880], [20000, 4080, 4080], [30000, 5400, 5400]],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $serviceCode): ?array
    {
        return self::all()[$serviceCode] ?? null;
    }

    /** @return array<string, mixed>|null */
    public static function fromMethod(?string $method): ?array
    {
        if (! $method) {
            return null;
        }

        foreach (self::all() as $serviceCode => $service) {
            if ($service['method'] === $method) {
                $service['service_code'] = $serviceCode;

                return $service;
            }
        }

        return null;
    }

    public static function label(string $serviceCode): string
    {
        return trans('laposte::app.service_'.$serviceCode);
    }

    public static function defaultCsv(string $serviceCode): string
    {
        $service = self::get($serviceCode);
        if (! $service) {
            return '';
        }

        $rows = array_map(
            static fn (array $tier): string => implode(',', [...$tier, 1]),
            $service['tiers']
        );

        return "max_weight_g,classic_cents,online_cents,enabled\n".implode("\n", $rows);
    }
}
