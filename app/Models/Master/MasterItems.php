<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class MasterItems extends Model
{
    use SoftDeletes,UserStamps,Rememberable;
    protected $guarded = [];

    public function Group() {
      return $this->belongTo('App\Models\Master\MasterGroups','groupKey','groupKey');
    }

    protected $casts = [
      'properties' => 'array',
    ];

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
    protected $rememberCacheTag = 'masteritems_query';
}
