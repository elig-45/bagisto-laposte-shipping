<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('laposte_rate_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('zone', 24)->default('metropolitan_france');
            $table->string('source_url', 500);
            $table->date('effective_from');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['zone', 'effective_from']);
        });
        Schema::create('laposte_rate_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rate_card_id')->constrained('laposte_rate_cards')->cascadeOnDelete();
            $table->unsignedInteger('max_weight_g');
            $table->unsignedInteger('classic_cents');
            $table->unsignedInteger('online_cents');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['rate_card_id', 'max_weight_g']);
        });
        Schema::create('laposte_product_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('product_id')->unique();
            $table->boolean('eligible')->default(false);
            $table->unsignedInteger('length_mm');
            $table->unsignedInteger('width_mm');
            $table->unsignedInteger('thickness_mm');
            $table->string('packing_rule', 32)->default('flat_stack');
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
        (new \Elig45\LaPosteShipping\Database\Seeders\Rate2026Seeder())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('laposte_product_profiles');
        Schema::dropIfExists('laposte_rate_tiers');
        Schema::dropIfExists('laposte_rate_cards');
    }
};
