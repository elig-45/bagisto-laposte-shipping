<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Tests\Unit;

use Elig45\LaPosteShipping\Services\QuoteCalculator;
use Elig45\LaPosteShipping\Services\ServiceCatalog;
use PHPUnit\Framework\TestCase;

final class ConfigurationContractTest extends TestCase
{
    public function testHasUniqueCarrierCodeAndCorrectBagistoClass(): void
    {
        $carriers = require dirname(__DIR__, 2).'/src/Config/carriers.php';
        self::assertArrayHasKey(QuoteCalculator::CODE, $carriers);
        self::assertSame(QuoteCalculator::CODE, $carriers[QuoteCalculator::CODE]['code']);
        self::assertFalse((bool) $carriers[QuoteCalculator::CODE]['active']);

        foreach (ServiceCatalog::all() as $service) {
            self::assertArrayHasKey($service['carrier_code'], $carriers);
            self::assertSame($service['class'], $carriers[$service['carrier_code']]['class']);
            self::assertFalse((bool) $carriers[$service['carrier_code']]['active']);
        }
    }

    public function testSettingsHaveSafeDefaultsAndGlobalScope(): void
    {
        $system = require dirname(__DIR__, 2).'/src/Config/system.php';
        $groups = array_column($system, null, 'key');

        foreach (ServiceCatalog::all() as $service) {
            $group = $groups['sales.carriers.'.$service['carrier_code']];
            $fields = array_column($group['fields'], null, 'name');
            self::assertFalse((bool) $fields['active']['default']);
            self::assertFalse((bool) $fields['packing_confirmed']['default']);
            foreach ($fields as $field) {
                self::assertFalse((bool) ($field['channel_based'] ?? false), $field['name']);
            }
        }
        $trackedLetterFields = array_column(
            $groups['sales.carriers.'.QuoteCalculator::CODE]['fields'],
            null,
            'name'
        );
        self::assertTrue((bool) $trackedLetterFields['title']['locale_based']);
        self::assertTrue((bool) $trackedLetterFields['description']['locale_based']);
    }

    public function testAclDescribesDistinctReadAndWritePermission(): void
    {
        $acl = require dirname(__DIR__, 2).'/src/Config/acl.php';
        self::assertSame('sales.laposte', $acl[0]['key']);
        self::assertSame('sales.laposte.manage', $acl[1]['key']);
        self::assertContains('admin.laposte.rates.import', $acl[1]['route']);
    }
}
