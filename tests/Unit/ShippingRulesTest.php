<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Tests\Unit;

use InvalidArgumentException;
use Elig45\LaPosteShipping\Services\CsvRates;
use Elig45\LaPosteShipping\Services\Decimal;
use Elig45\LaPosteShipping\Services\MetropolitanFrance;
use Elig45\LaPosteShipping\Services\PackingPolicy;
use Elig45\LaPosteShipping\Services\PriceAdjustments;
use Elig45\LaPosteShipping\Services\RateSelection;
use Elig45\LaPosteShipping\Services\ServiceCatalog;
use Elig45\LaPosteShipping\Services\WeightPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShippingRulesTest extends TestCase
{
    private function tiers(): array
    {
        return (new CsvRates())->parse(file_get_contents(dirname(__DIR__, 2).'/data/tarifs-2026.csv'));
    }

    public static function boundaryCases(): array
    {
        return [
            '20g inclusive' => [20_000, 'classic', 202],
            '20.01g' => [20_010, 'classic', 360],
            '100g inclusive' => [100_000, 'classic', 360],
            '100.01g' => [100_010, 'classic', 574],
            '250g inclusive' => [250_000, 'classic', 574],
            '2000g' => [2_000_000, 'classic', 1164],
            '2000.001g forbidden' => [2_000_001, 'classic', null],
            'online 20g' => [20_000, 'online', 199],
            'online 2kg' => [2_000_000, 'online', 1156],
        ];
    }

    #[DataProvider('boundaryCases')]
    public function testRateBoundaries(int $mg, string $mode, ?int $expected): void
    {
        self::assertSame($expected, (new RateSelection())->select($this->tiers(), $mg, $mode));
    }

    public function testPackWeightAndQuantity(): void
    {
        $weight = new WeightPolicy();
        self::assertSame(20000, $weight->totalMg([['weight' => '0.015', 'quantity' => 1, 'physical' => true]], 'kgs', ['packaging_weight_g' => '5']));
        self::assertSame(100000, $weight->totalMg([['weight' => '0.045', 'quantity' => 2, 'physical' => true]], 'kgs', ['packaging_weight_g' => '10']));
        self::assertSame(35000, $weight->totalMg([['weight' => '0.015', 'quantity' => 2, 'physical' => true]], 'kgs', ['packaging_weight_g' => '5']));
    }

    public function testNoInvisibleWeightFallback(): void
    {
        $service = new WeightPolicy();
        self::assertNull($service->totalMg([['weight'=>null,'quantity'=>1,'physical'=>true]], 'kgs', ['missing_weight_policy'=>'disable']));
        self::assertSame(20000, $service->totalMg([['weight'=>null,'quantity'=>1,'physical'=>true]], 'kgs', ['missing_weight_policy'=>'substitute','substitute_weight_g'=>'15','packaging_weight_g'=>'5']));
        self::assertNull($service->totalMg([['weight'=>'unknown','quantity'=>1,'physical'=>true]], 'kgs', ['missing_weight_policy'=>'substitute','substitute_weight_g'=>'15']));
        self::assertNull($service->totalMg([['weight'=>'0.1','quantity'=>1,'physical'=>false]], 'kgs', []));
    }

    public function testConversionAndInputValidation(): void
    {
        self::assertSame(20010, Decimal::gramsMg('20.01'));
        self::assertSame(453592370, Decimal::weightMg('1', 'lbs'));
        self::assertSame(202, Decimal::euroCents('2.02'));
        $this->expectException(InvalidArgumentException::class);
        Decimal::weightMg('-1', 'kgs');
    }

    public static function destinations(): array
    {
        return [
            'Paris' => ['FR', '75001', true],
            'Corsica' => ['FR', '20100', true],
            'Guadeloupe' => ['FR','97100', false],
            'Martinique' => ['FR','97200', false],
            'Mayotte' => ['FR','97600', false],
            'Monaco' => ['FR','98000', false],
            'Andorra' => ['AD','AD500', false],
            'Belgium' => ['BE','1000', false],
        ];
    }

    #[DataProvider('destinations')]
    public function testDestinations(string $country, string $postcode, bool $expected): void
    {
        self::assertSame($expected, (new MetropolitanFrance())->accepts($country, $postcode));
    }

    public function testUnprovenOrTooThickPackingFails(): void
    {
        $service = new PackingPolicy();
        $envelope = ['envelope_length_mm'=>240,'envelope_width_mm'=>160,'envelope_thickness_mm'=>1];
        $item = ['eligible'=>true,'packing_rule'=>'flat_stack','length_mm'=>100,'width_mm'=>80,'thickness_mm'=>10,'quantity'=>1];
        self::assertTrue($service->accepts([$item], $envelope));
        self::assertFalse($service->accepts([array_replace($item,['quantity'=>3])], $envelope));
        self::assertFalse($service->accepts([array_replace($item,['eligible'=>false])], $envelope));
        self::assertFalse($service->accepts([array_replace($item,['length_mm'=>250])], $envelope));
        self::assertFalse($service->accepts([], $envelope));
    }

    public function testAdjustmentAndFreeThreshold(): void
    {
        $service = new PriceAdjustments();
        self::assertSame(352, $service->apply(202, ['packaging_cost_eur'=>'0.50','surcharge_type'=>'fixed','surcharge_value'=>'1'], 500));
        self::assertSame(222, $service->apply(202, ['surcharge_type'=>'percent','surcharge_value'=>'10'], 500));
        self::assertSame(0, $service->apply(202, ['free_threshold_eur'=>'20'], 2000));
        self::assertSame(202, $service->apply(202, ['free_threshold_eur'=>'20'], 1999));
        self::assertSame(219, $service->apply(199, ['packaging_cost_eur'=>'0.20'], 1999));
    }

    public function testDisabledTierNeverFallsThrough(): void
    {
        $tiers = $this->tiers();
        $tiers[0]['enabled'] = false;
        self::assertNull((new RateSelection())->select($tiers, 20000, 'classic'));
    }

    public function testColissimo2026OfficialHomeTariffsAndWeightBoundaries(): void
    {
        $service = ServiceCatalog::get('colissimo_domicile');
        self::assertNotNull($service);
        self::assertSame([
            [250, 549, 549], [500, 759, 759], [750, 929, 929], [1000, 959, 959],
            [2000, 1119, 1119], [5000, 1739, 1739], [10000, 2529, 2529],
            [15000, 3199, 3199], [30000, 3959, 3959],
        ], $service['tiers']);

        self::assertSame(
            (new CsvRates())->parse(file_get_contents(dirname(__DIR__, 2).'/data/tarifs-colissimo-domicile-2026.csv'), 'colissimo_domicile'),
            (new CsvRates())->parse(ServiceCatalog::defaultCsv('colissimo_domicile'), 'colissimo_domicile')
        );

        $tiers = array_map(static fn (array $tier): array => [
            'max_weight_g' => $tier[0],
            'classic_cents' => $tier[1],
            'online_cents' => $tier[2],
            'enabled' => true,
        ], $service['tiers']);
        $selection = new RateSelection();

        self::assertSame(549, $selection->select($tiers, 250_000, 'classic', 30_000_000));
        self::assertSame(759, $selection->select($tiers, 250_001, 'classic', 30_000_000));
        self::assertSame(3959, $selection->select($tiers, 30_000_000, 'classic', 30_000_000));
        self::assertNull($selection->select($tiers, 30_000_001, 'classic', 30_000_000));
    }

    public function testColissimoRequiresTheConfiguredPackageToFitItsLimits(): void
    {
        $policy = new PackingPolicy();
        $limits = ServiceCatalog::get('colissimo_domicile')['package_limits'];
        $package = [
            'packing_type' => 'parcel',
            'package_limits' => $limits,
            'package_length_mm' => 300,
            'package_width_mm' => 150,
            'package_height_mm' => 30,
        ];
        $item = [
            'eligible' => true,
            'packing_rule' => 'flat_stack',
            'length_mm' => 200,
            'width_mm' => 100,
            'thickness_mm' => 10,
            'quantity' => 1,
        ];

        self::assertTrue($policy->accepts([$item], $package));
        self::assertFalse($policy->accepts([array_replace($item, ['quantity' => 4])], $package));
        self::assertFalse($policy->accepts([$item], array_replace($package, ['package_length_mm' => 210])));
        self::assertFalse($policy->accepts([$item], array_replace($package, ['package_width_mm' => 100])));
        self::assertFalse($policy->accepts([$item], array_replace($package, ['package_height_mm' => 9])));
        self::assertFalse($policy->accepts([$item], array_replace($package, ['package_length_mm' => 1001])));
        self::assertFalse($policy->accepts([$item], array_replace($package, [
            'package_length_mm' => 701,
            'package_width_mm' => 300,
            'package_height_mm' => 100,
        ])));
    }

    public function testRejectsMalformedCsv(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new CsvRates())->parse("max_weight_g,classic_cents,online_cents,enabled\n100,100,100,1\n20,20,20,1\n2000,300,300,1");
    }
}
