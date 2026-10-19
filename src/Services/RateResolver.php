<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Services;

use Carbon\CarbonInterface;
use Elig45\LaPosteShipping\Models\RateCard;

final class RateResolver
{
    public function __construct(private readonly RateSelection $selection) {}

    public function resolve(int $weightMg, string $mode, ?CarbonInterface $at = null): ?array
    {
        return $this->resolveForService('lettre_verte_suivie', $weightMg, $mode, $at);
    }

    public function resolveForService(string $serviceCode, int $weightMg, string $mode, ?CarbonInterface $at = null): ?array
    {
        $service = ServiceCatalog::get($serviceCode);
        if (! $service) {
            return null;
        }

        $date = ($at ?? now())->toDateString();
        $card = RateCard::query()
            ->where('service_code', $serviceCode)
            ->where('enabled', true)
            ->where('zone', 'metropolitan_france')
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')->first();
        if (! $card) {
            return null;
        }
        $tiers = $card->tiers->map(static fn ($tier): array => [
            'max_weight_g' => $tier->max_weight_g,
            'classic_cents' => $tier->classic_cents,
            'online_cents' => $tier->online_cents,
            'enabled' => $tier->enabled,
        ])->all();
        $cents = $this->selection->select($tiers, $weightMg, $mode, (int) $service['max_weight_g'] * 1000);
        return $cents === null ? null : ['cents' => $cents, 'card_id' => (int) $card->id];
    }
}
