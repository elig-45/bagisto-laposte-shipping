<?php

declare(strict_types=1);

namespace Elig45\LaPosteShipping\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductProfile extends Model
{
    protected $table = 'laposte_product_profiles';
    protected $guarded = ['id'];
    protected $casts = ['eligible' => 'boolean', 'length_mm' => 'integer', 'width_mm' => 'integer', 'thickness_mm' => 'integer'];
}
