<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class Rate2026Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // Idempotent and intentionally never overwrites historical prices.
            $existing = DB::table('laposte_rate_cards')
                ->where('zone', 'metropolitan_france')
                ->where('effective_from', '2026-01-01')->first();
            if ($existing) {
                return;
            }

            $id = DB::table('laposte_rate_cards')->insertGetId([
                'zone' => 'metropolitan_france',
                'source_url' => 'https://www.laposte.fr/tarif-lettre-suivie',
                'effective_from' => '2026-01-01',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ([[20,202,199],[100,360,347],[250,574,568],[500,791,771],[1000,979,964],[2000,1164,1156]] as [$max,$classic,$online]) {
                DB::table('laposte_rate_tiers')->insert([
                    'rate_card_id' => $id,
                    'max_weight_g' => $max,
                    'classic_cents' => $classic,
                    'online_cents' => $online,
                    'enabled' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }
}
