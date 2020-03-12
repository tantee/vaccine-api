<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Products extends Model
{
    use SoftDeletes,UserStamps,Rememberable;
    protected $primaryKey = 'productCode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
      'specification' => 'array',
      'childProducts' => 'array',
      'itemizedProducts' => 'array',
      'price1' => 'float',
      'price2' => 'float',
      'price3' => 'float',
      'price4' => 'float',
      'price5' => 'float',
      'cgdPrice' => 'float',
    ];

    public function scopeActive($query) {
      return $query->where('isActive',true);
    }

    public function scopeSelectable($query) {
      return $query->where('isActive',true)->where('isHidden',false);
    }

    public function scopeStockable($query) {
      return $query->where('productType','medicine')->orWhere('productType','supply');
    }

    public static function boot() {
        static::saved(function($model) {
            $model::flushCache();
        });

        static::deleted(function($model) {
            $model::flushCache();
        });

        static::restored(function($model) {
            $model::flushCache();
        });

        parent::boot();
    }

    protected $rememberFor = 5;
    protected $rememberCacheTag = 'products_query';
}
