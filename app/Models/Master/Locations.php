<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Locations extends Model
{
    //
    use SoftDeletes,UserStamps,Rememberable;

    protected $primaryKey = 'locationCode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
      'subunits' => 'array',
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
    protected $rememberCacheTag = 'locations_query';
}
