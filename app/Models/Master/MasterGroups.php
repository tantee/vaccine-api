<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class MasterGroups extends Model
{
    use SoftDeletes,UserStamps,Rememberable;

    protected $primaryKey = 'groupKey';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function Items() {
      return $this->hasMany('App\Models\Master\MasterItems','groupKey','groupKey')->orderBy('ordering')->orderBy('itemCode');
    }

    protected $casts = [
      'defaultProperties' => 'array',
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
    protected $rememberCacheTag = 'mastergroups_query';
}
