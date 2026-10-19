<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Tests\Integration;

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MEJF\LaPosteShipping\Models\RateCard;
use MEJF\LaPosteShipping\Services\RateResolver;
use Orchestra\Testbench\TestCase;

/** Run with Orchestra Testbench and its SQLite database, independently of Bagisto core. */
final class RateHistoryTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('laposte_rate_cards', static function (Blueprint $t): void {
            $t->id(); $t->string('zone');
            $t->string('source_url'); $t->date('effective_from'); $t->boolean('enabled'); $t->timestamps();
        });
        Schema::create('laposte_rate_tiers', static function (Blueprint $t): void {
            $t->id(); $t->foreignId('rate_card_id'); $t->unsignedInteger('max_weight_g');
            $t->unsignedInteger('classic_cents'); $t->unsignedInteger('online_cents');
            $t->boolean('enabled'); $t->timestamps();
        });
    }

    private function card(string $from, int $classic): RateCard
    {
        $card = RateCard::create([
            'zone'=>'metropolitan_france',
            'source_url'=>'https://www.laposte.fr/tarif-lettre-suivie',
            'effective_from'=>$from, 'enabled'=>true,
        ]);
        $card->tiers()->create(['max_weight_g'=>20,'classic_cents'=>$classic,'online_cents'=>$classic-3,'enabled'=>true]);
        return $card;
    }

    public function testSingleSharedGridIsSelected(): void
    {
        $this->card('2026-01-01', 202);
        $rates = $this->app->make(RateResolver::class);
        self::assertSame(202, $rates->resolve(20000, 'classic', CarbonImmutable::parse('2026-10-19'))['cents']);
    }

    public function testDatedGridDoesNotMutatePastQuote(): void
    {
        $this->card('2026-01-01', 202);
        $this->card('2027-01-01', 225);
        $rates = $this->app->make(RateResolver::class);
        self::assertSame(202, $rates->resolve(20000, 'classic', CarbonImmutable::parse('2026-12-31'))['cents']);
        self::assertSame(225, $rates->resolve(20000, 'classic', CarbonImmutable::parse('2027-01-02'))['cents']);
    }

    public function testUnavailableWhenNoEffectiveRateCard(): void
    {
        $this->card('2027-01-01', 225);
        self::assertNull($this->app->make(RateResolver::class)->resolve(20000, 'classic', CarbonImmutable::parse('2026-10-19')));
    }
}
