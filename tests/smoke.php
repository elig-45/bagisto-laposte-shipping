<?php

declare(strict_types=1);

/** Dependency-free smoke checks for the pricing and eligibility core. */
spl_autoload_register(static function (string $name): void {
    $prefix = 'MEJF\\LaPosteShipping\\';
    if (! str_starts_with($name, $prefix)) {
        return;
    }
    $file = dirname(__DIR__).'/src/'.str_replace('\\', '/', substr($name, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
});

use MEJF\LaPosteShipping\Services\CsvRates;
use MEJF\LaPosteShipping\Services\Decimal;
use MEJF\LaPosteShipping\Services\MetropolitanFrance;
use MEJF\LaPosteShipping\Services\PackingPolicy;
use MEJF\LaPosteShipping\Services\PriceAdjustments;
use MEJF\LaPosteShipping\Services\RateSelection;
use MEJF\LaPosteShipping\Services\WeightPolicy;

$assertions = 0;
$check = static function (mixed $actual, mixed $expected, string $message) use (&$assertions): void {
    $assertions++;
    if ($actual !== $expected) {
        throw new RuntimeException("$message: expected ".var_export($expected, true).', got '.var_export($actual, true));
    }
};
$weight = new WeightPolicy();
$selector = new RateSelection();
$tiers = (new CsvRates())->parse(file_get_contents(dirname(__DIR__).'/data/tarifs-2026.csv'));
$settings = ['packaging_weight_g' => '5', 'missing_weight_policy' => 'disable', 'substitute_weight_g' => '0'];
$check($weight->totalMg([['weight' => '0.015', 'quantity' => 1, 'physical' => true]], 'kgs', $settings), 20_000, '15g plus 5g');
$check($selector->select($tiers, 20_000, 'classic'), 202, '20g inclusive');
$check($selector->select($tiers, 20_010, 'classic'), 360, '20.01g next tier');
$check($selector->select($tiers, $weight->totalMg([['weight'=>'0.045','quantity'=>2,'physical'=>true]], 'kgs', ['packaging_weight_g'=>'10']), 'classic'), 360, 'two lots + packing = 100g');
$check($selector->select($tiers, 100_010, 'classic'), 574, '100.01g');
$check($selector->select($tiers, 2_000_000, 'classic'), 1164, '2000g');
$check($selector->select($tiers, 2_000_001, 'classic'), null, '2000g exceeded');
$check($selector->select($tiers, 2_000_000, 'online'), 1156, 'online 2kg');
$check($selector->select($tiers, 20_000, 'online'), 199, 'online 20g');
$check($weight->totalMg([['weight'=>'0','quantity'=>1,'physical'=>true]], 'kgs', $settings), null, 'missing zero weight unavailable');
$check($weight->totalMg([['weight'=>'','quantity'=>1,'physical'=>true]], 'kgs', ['missing_weight_policy'=>'substitute','substitute_weight_g'=>'15','packaging_weight_g'=>'5']), 20_000, 'explicit substitute');
$check($weight->totalMg([['weight'=>'INVALID','quantity'=>1,'physical'=>true]], 'kgs', ['missing_weight_policy'=>'substitute','substitute_weight_g'=>'15']), null, 'invalid cannot substitute');
$check($weight->totalMg([['weight'=>'0.1','quantity'=>1,'physical'=>false]], 'kgs', []), null, 'virtual-only cart has no shipping');
$check($weight->totalMg([['weight'=>'2.001','quantity'=>1,'physical'=>true]], 'kgs', []), null, 'overweight cart rejected');
$check(Decimal::gramsMg('20.01'), 20010, 'decimal grams');
$check(Decimal::weightMg('1', 'lbs'), 453592370, 'lbs conversion');
$check(Decimal::euroCents('2.02'), 202, 'decimal euros to cents');
$check((new MetropolitanFrance())->accepts('FR', '75001'), true, 'Paris');
$check((new MetropolitanFrance())->accepts('FR', '20100'), true, 'Corsica');
$check((new MetropolitanFrance())->accepts('FR', '97100'), false, 'Guadeloupe');
$check((new MetropolitanFrance())->accepts('FR', '98000'), false, 'Monaco');
$check((new MetropolitanFrance())->accepts('AD', 'AD500'), false, 'Andorra');
$check((new MetropolitanFrance())->accepts('BE', '1000'), false, 'Belgium');
$policy = new PackingPolicy();
$item = ['length_mm'=>100,'width_mm'=>80,'thickness_mm'=>10,'quantity'=>1,'eligible'=>true,'packing_rule'=>'flat_stack'];
$check($policy->accepts([$item], ['envelope_length_mm'=>240,'envelope_width_mm'=>160,'envelope_thickness_mm'=>1]), true, 'thin envelope');
$check($policy->accepts([array_merge($item,['thickness_mm'=>31])], ['envelope_length_mm'=>240,'envelope_width_mm'=>160,'envelope_thickness_mm'=>1]), false, 'thick parcel');
$check($policy->accepts([array_merge($item,['quantity'=>3])], ['envelope_length_mm'=>240,'envelope_width_mm'=>160,'envelope_thickness_mm'=>1]), false, 'stack exceeds 3cm');
$check($policy->accepts([array_merge($item,['eligible'=>false])], ['envelope_length_mm'=>240,'envelope_width_mm'=>160,'envelope_thickness_mm'=>1]), false, 'ineligible SKU');
$check($policy->accepts([array_merge($item,['length_mm'=>250])], ['envelope_length_mm'=>240,'envelope_width_mm'=>160,'envelope_thickness_mm'=>1]), false, 'SKU too long');
$adjustments = new PriceAdjustments();
$check($adjustments->apply(202,['packaging_cost_eur'=>'0.50','surcharge_type'=>'fixed','surcharge_value'=>'1.00'],500), 352, 'fixed packaging and surcharge');
$check($adjustments->apply(202,['packaging_cost_eur'=>'0','surcharge_type'=>'percent','surcharge_value'=>'10'],500), 222, '10% round to cents');
$check($adjustments->apply(202,['packaging_cost_eur'=>'0.50','surcharge_type'=>'none','free_threshold_eur'=>'20'],2000), 0, 'free at threshold');
$check($adjustments->apply(202,['packaging_cost_eur'=>'0.50','surcharge_type'=>'none','free_threshold_eur'=>'20'],1999), 252, 'not free below threshold');
$check($selector->select([array_merge($tiers[0],['enabled'=>false]),...array_slice($tiers,1)], 20000,'classic'),null,'disabled tier not skipped');
$check($selector->select($tiers, 100000, 'classic'), 360, '100g inclusive');
$check($selector->select($tiers, 100001, 'classic'), 574, '100g plus 1mg');
$check($weight->totalMg([['weight'=>'0.015','quantity'=>2,'physical'=>true]],'kgs',['packaging_weight_g'=>'5']),35000,'quantity reprice increases mass');
$check($adjustments->apply(202,['packaging_cost_eur'=>'0','surcharge_type'=>'none'],0),202,'default settings');
$check($adjustments->apply(199,['packaging_cost_eur'=>'0.20','surcharge_type'=>'none'],0),219,'settings with packaging cost');
$badCsv = false;
try { (new CsvRates())->parse("max_weight_g,classic_cents,online_cents,enabled\n100,300,300,1\n20,200,200,1\n2000,1000,1000,1"); } catch (InvalidArgumentException) { $badCsv = true; }
$check($badCsv,true,'unsorted CSV rejected');
$configuration = require dirname(__DIR__).'/src/Config/system.php';
$check($configuration[0]['key'], 'sales.carriers.laposte_lettre_suivie', 'shipping section');
foreach ($configuration[0]['fields'] as $field) {
    $check((bool) ($field['channel_based'] ?? false), false, 'global field '.$field['name']);
}
$check((bool) ($configuration[0]['fields'][0]['default']), false, 'shipping disabled by default');
echo "OK: {$assertions} assertions\n";
