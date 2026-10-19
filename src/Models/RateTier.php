<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Models;

use Illuminate\Database\Eloquent\Model;

final class RateTier extends Model
{
    protected $table = 'laposte_rate_tiers';
    protected $guarded = ['id'];
    protected $casts = [
        'max_weight_g' => 'integer', 'classic_cents' => 'integer',
        'online_cents' => 'integer', 'enabled' => 'boolean',
    ];
}
