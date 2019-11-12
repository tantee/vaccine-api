<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Payers extends Model
{
    //
    use SoftDeletes,UserStamps,Rememberable;

    protected $primaryKey = 'payerCode';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $guarded = [];

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

    protected $rememberFor = 60;
    protected $rememberCacheTag = 'payers_query';
}
