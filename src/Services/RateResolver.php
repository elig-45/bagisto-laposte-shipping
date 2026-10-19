<?php

declare(strict_types=1);

namespace MEJF\LaPosteShipping\Services;

use Carbon\CarbonInterface;
use MEJF\LaPosteShipping\Models\RateCard;

final class RateResolver
{
    public function __construct(private readonly RateSelection $selection) {}

    public function resolve(int $weightMg, string $mode, ?CarbonInterface $at = null): ?array
    {
        $date = ($at ?? now())->toDateString();
        $card = RateCard::query()
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
        $cents = $this->selection->select($tiers, $weightMg, $mode);
        return $cents === null ? null : ['cents' => $cents, 'card_id' => (int) $card->id];
    }
}
