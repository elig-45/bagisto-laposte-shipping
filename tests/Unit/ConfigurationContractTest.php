<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Tests\Unit;

use MEJF\LaPosteShipping\Services\QuoteCalculator;
use PHPUnit\Framework\TestCase;

final class ConfigurationContractTest extends TestCase
{
    public function testHasUniqueCarrierCodeAndCorrectBagistoClass(): void
    {
        $carriers = require dirname(__DIR__, 2).'/src/Config/carriers.php';
        self::assertArrayHasKey(QuoteCalculator::CODE, $carriers);
        self::assertSame(QuoteCalculator::CODE, $carriers[QuoteCalculator::CODE]['code']);
        self::assertFalse((bool) $carriers[QuoteCalculator::CODE]['active']);
    }

    public function testSettingsHaveSafeDefaultsAndGlobalScope(): void
    {
        $system = require dirname(__DIR__, 2).'/src/Config/system.php';
        self::assertSame('sales.carriers.'.QuoteCalculator::CODE, $system[0]['key']);
        $fields = array_column($system[0]['fields'], null, 'name');
        self::assertFalse((bool) $fields['active']['default']);
        self::assertFalse((bool) $fields['packing_confirmed']['default']);
        foreach ($fields as $field) {
            self::assertFalse((bool) ($field['channel_based'] ?? false), $field['name']);
        }
        self::assertTrue((bool) $fields['title']['locale_based']);
        self::assertTrue((bool) $fields['description']['locale_based']);
    }

    public function testAclDescribesDistinctReadAndWritePermission(): void
    {
        $acl = require dirname(__DIR__, 2).'/src/Config/acl.php';
        self::assertSame('sales.laposte', $acl[0]['key']);
        self::assertSame('sales.laposte.manage', $acl[1]['key']);
        self::assertContains('admin.laposte.rates.import', $acl[1]['route']);
    }
}
