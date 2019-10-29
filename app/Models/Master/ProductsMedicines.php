<?php

namespace App\Models\Master;

use  App\Models\Master\Products;

class ProductsMedicines extends Products
{
    public static function boot() {
        parent::boot();

        static::addGlobalScope('Medicines', function (Builder $builder) {
            $builder->where('productType', 'medicine');
        });
    }
}
