<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RateCard extends Model
{
    protected $table = 'laposte_rate_cards';
    protected $guarded = ['id'];
    protected $casts = ['effective_from' => 'date:Y-m-d', 'enabled' => 'boolean'];

    public function tiers(): HasMany
    {
        return $this->hasMany(RateTier::class, 'rate_card_id')->orderBy('max_weight_g');
    }
}
