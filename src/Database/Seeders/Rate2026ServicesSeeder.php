<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Database\Seeders;

use Elig45\LaPosteShipping\Services\ServiceCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class Rate2026ServicesSeeder extends Seeder
{
    /**
     * Public 2026 tariffs for metropolitan France. The current tracked-letter
     * rate card remains untouched; this adds only the new service cards.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (ServiceCatalog::all() as $serviceCode => $service) {
                $exists = DB::table('laposte_rate_cards')
                    ->where('service_code', $serviceCode)
                    ->where('zone', 'metropolitan_france')
                    ->where('effective_from', '2026-01-01')
                    ->exists();

                if ($exists) {
                    continue;
                }

                $now = now();
                $cardId = DB::table('laposte_rate_cards')->insertGetId([
                    'service_code' => $serviceCode,
                    'zone' => 'metropolitan_france',
                    'source_url' => $service['source_url'],
                    'effective_from' => '2026-01-01',
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($service['tiers'] as [$maxWeight, $classic, $online]) {
                    DB::table('laposte_rate_tiers')->insert([
                        'rate_card_id' => $cardId,
                        'max_weight_g' => $maxWeight,
                        'classic_cents' => $classic,
                        'online_cents' => $online,
                        'enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
}
