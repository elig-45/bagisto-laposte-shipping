<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        app(\Elig45\LaPosteShipping\Database\Seeders\Rate2026ServicesSeeder::class)->run();
    }

    public function down(): void
    {
        $cardIds = DB::table('laposte_rate_cards')
            ->where('service_code', 'colissimo_domicile')
            ->where('effective_from', '2026-01-01')
            ->pluck('id');

        DB::table('laposte_rate_tiers')->whereIn('rate_card_id', $cardIds)->delete();
        DB::table('laposte_rate_cards')->whereIn('id', $cardIds)->delete();
    }
};
