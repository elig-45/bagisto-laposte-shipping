<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

use InvalidArgumentException;

final class CsvRates
{
    /** @return list<array{max_weight_g:int,classic_cents:int,online_cents:int,enabled:bool}> */
    public function parse(string $csv, string $serviceCode = 'lettre_verte_suivie'): array
    {
        $service = ServiceCatalog::get($serviceCode);
        if (! $service) {
            throw new InvalidArgumentException('Unknown shipping service.');
        }

        $maxWeight = (int) $service['max_weight_g'];
        if (strlen($csv) > 32768) {
            throw new InvalidArgumentException('CSV file too large.');
        }
        $rows = [];
        $previous = 0;
        foreach (preg_split('/\r\n|\r|\n/', trim($csv)) as $line) {
            if ($line === '') {
                continue;
            }
            $parts = str_getcsv($line, ',', '"', '');
            if (($parts[0] ?? null) === 'max_weight_g') {
                if ($rows !== []) {
                    throw new InvalidArgumentException('CSV header must be first.');
                }
                continue;
            }
            if (count($parts) !== 4 || array_filter($parts, static fn ($x) => ! preg_match('/^\d+$/D', trim($x))) !== []) {
                throw new InvalidArgumentException('Invalid CSV tier row.');
            }
            [$weight, $classic, $online, $enabled] = array_map('intval', $parts);
            if ($weight <= $previous || $weight > $maxWeight || $classic > 1_000_000
                || $online > 1_000_000 || ! in_array($enabled, [0, 1], true) || count($rows) >= 30) {
                throw new InvalidArgumentException('Unsorted, duplicate, or invalid tier.');
            }
            $rows[] = ['max_weight_g' => $weight, 'classic_cents' => $classic, 'online_cents' => $online, 'enabled' => (bool) $enabled];
            $previous = $weight;
        }
        if ($rows === [] || $previous !== $maxWeight) {
            throw new InvalidArgumentException('Last tier must end at '.$maxWeight.' g.');
        }
        return $rows;
    }
}
