<?php

use Elig45\LaPosteShipping\Database\Seeders\Rate2026ServicesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('laposte_rate_cards', function (Blueprint $table): void {
            $table->string('service_code', 32)->default('lettre_verte_suivie')->after('id');
            $table->dropUnique('laposte_rate_cards_zone_effective_from_unique');
            $table->unique(
                ['service_code', 'zone', 'effective_from'],
                'laposte_rate_cards_service_zone_date_unique'
            );
        });

        DB::table('laposte_rate_cards')->whereNull('service_code')
            ->update(['service_code' => 'lettre_verte_suivie']);

        app(Rate2026ServicesSeeder::class)->run();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $addedCardIds = DB::table('laposte_rate_cards')
                ->where('service_code', '<>', 'lettre_verte_suivie')
                ->pluck('id');

            DB::table('laposte_rate_tiers')->whereIn('rate_card_id', $addedCardIds)->delete();
            DB::table('laposte_rate_cards')->whereIn('id', $addedCardIds)->delete();
        });

        Schema::table('laposte_rate_cards', function (Blueprint $table): void {
            $table->dropUnique('laposte_rate_cards_service_zone_date_unique');
            $table->dropColumn('service_code');
            $table->unique(['zone', 'effective_from']);
        });
    }
};
